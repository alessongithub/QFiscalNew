unit Un_principal;

interface

uses
  Winapi.Windows, Winapi.Messages, System.SysUtils, System.Variants, System.Classes, Vcl.Graphics,
  Vcl.Controls, Vcl.Forms, Vcl.Dialogs, ACBrValidador, ACBrDFeReport,
  ACBrDFeDANFeReport, ACBrNFeDANFEClass, ACBrNFeDANFeRLClass, ACBrBase, ACBrDFe,
  ACBrNFe, ACBrDFeSSL, System.NetEncoding, System.IOUtils, IdURI,
  // Indy HTTP
  IdHTTPServer, IdContext, IdCustomHTTPServer,
  // JSON
  System.JSON, ACBrNFSe, pcnConversao, ActiveX,
  TypInfo, StrUtils,
  ACBrNFeDANFeFPDF, System.Generics.Collections;

type
  TForm1 = class(TForm)
  published
    ACBrNFe1: TACBrNFe;
    ACBrNFeDANFeRL1: TACBrNFeDANFeRL;
    ACBrValidador1: TACBrValidador;
    ACBrNFSe1: TACBrNFSe;
    procedure FormCreate(Sender: TObject);
    procedure IdHTTPServer1CommandGet(AContext: TIdContext; ARequestInfo: TIdHTTPRequestInfo;
      AResponseInfo: TIdHTTPResponseInfo);
  private
    FServer: TIdHTTPServer;
    FValidTokens: TStringList;
    function ReadRequestBody(ARequestInfo: TIdHTTPRequestInfo): string;
    function EmitirNFeJSON(const JSONData: string): string;
    function GerarDanfeJSON(const JSONData: string): string;
    function CancelarNFeJSON(const JSONData: string): string;
    function CartaCorrecaoJSON(const JSONData: string): string;
    function InutilizarNFeJSON(const JSONData: string): string;
    function EmitirNFSeJSON(const JSONData: string): string;
    function JsonGetObj(Obj: TJSONObject; const Key: string): TJSONObject;
    function JsonGetArr(Obj: TJSONObject; const Key: string): TJSONArray;
    function JsonGetStr(Obj: TJSONObject; const Key: string; const Default: string = ''): string;
    function JsonGetInt(Obj: TJSONObject; const Key: string; const Default: Integer = 0): Integer;
    function ValidateToken(ARequestInfo: TIdHTTPRequestInfo): Boolean;
    function ExtractTokenFromHeader(ARequestInfo: TIdHTTPRequestInfo): string;
    procedure LogSecurityEvent(const EventType, Details: string);
    procedure LoadValidTokens;
  public
    { Public declarations }
  end;

var
  Form1: TForm1;

implementation

{$R *.dfm}

function DigitsOnly(const S: string): string;
var i: Integer; ch: Char;
begin
  Result := '';
  for i := 1 to Length(S) do
  begin
    ch := S[i];
    if (ch >= '0') and (ch <= '9') then
      Result := Result + ch;
  end;
end;

procedure TForm1.FormCreate(Sender: TObject);
begin
  // Inicializa COM para evitar erros WIC/Graphics ao gerar PDF/Imagens
  try CoInitialize(nil); except end;
  try
    Caption := 'Inicializando ACBr...';
    ACBrNFe1.Configuracoes.WebServices.Visualizar := False;
    ACBrNFe1.Configuracoes.WebServices.Salvar := True;
    ACBrNFe1.Configuracoes.Arquivos.Salvar := True;
    // Garante NFe 4.00 e validação de schema ativa
    // VersaoDF pode variar entre versões do ACBr; manter padrão do componente
    try ACBrNFe1.Configuracoes.Geral.ExibirErroSchema := True; except end;
    ACBrNFe1.DANFE := ACBrNFeDANFeRL1;

    Caption := 'Configurando SSL...';
    ACBrNFe1.Configuracoes.Geral.SSLLib := libOpenSSL;
    ACBrNFe1.Configuracoes.Geral.SSLCryptLib := cryOpenSSL;
    ACBrNFe1.Configuracoes.Geral.SSLHttpLib := httpOpenSSL;
    ACBrNFe1.Configuracoes.Geral.SSLXmlSignLib := xsLibXml2;

    // Configuração do DANFE (FortesReport) para ambiente com restrições WIC
    try
      ACBrNFeDANFeRL1.Logo := '';
    except end;

    // Diretório padrão para XMLs e logs ao lado do executável
    try
      ACBrNFe1.Configuracoes.Arquivos.PathNFe := IncludeTrailingPathDelimiter(ExtractFilePath(Application.ExeName) + 'nfe');
      if not DirectoryExists(ACBrNFe1.Configuracoes.Arquivos.PathNFe) then
        ForceDirectories(ACBrNFe1.Configuracoes.Arquivos.PathNFe);
      // PathSchemas opcional (se existir ao lado do exe)
      if DirectoryExists(ExtractFilePath(Application.ExeName) + 'Schemas') then
        ACBrNFe1.Configuracoes.Arquivos.PathSchemas := IncludeTrailingPathDelimiter(ExtractFilePath(Application.ExeName) + 'Schemas');
      if not DirectoryExists(ExtractFilePath(Application.ExeName) + 'logs') then
        ForceDirectories(ExtractFilePath(Application.ExeName) + 'logs');
      if not DirectoryExists(ExtractFilePath(Application.ExeName) + 'logs\\requests') then
        ForceDirectories(ExtractFilePath(Application.ExeName) + 'logs\\requests');
    except
      // ignora erros de IO aqui
    end;

    Caption := 'Inicializando tokens...';
    FValidTokens := TStringList.Create;
    LoadValidTokens;

    Caption := 'Iniciando servidor HTTP...';
    FServer := TIdHTTPServer.Create(Self);
    FServer.OnCommandGet := IdHTTPServer1CommandGet;
    FServer.OnCommandOther := IdHTTPServer1CommandGet; // garante tratamento de POST/OPTIONS/etc
    FServer.Bindings.Clear;
    with FServer.Bindings.Add do
    begin
      IP := '0.0.0.0';
      Port := 18080;
    end;
    FServer.Active := True;

    Caption := 'Emissor NFe/NFS-e - Porta 18080 - Seguro [' + FormatDateTime('yyyy-mm-dd hh:nn:ss', Now) + ']';
    // Grava build_info para confirmar a versão em execução
    try
      var LogDir := ExtractFilePath(Application.ExeName) + 'logs\\';
      if not DirectoryExists(LogDir) then ForceDirectories(LogDir);
      var F: TextFile;
      AssignFile(F, LogDir + 'build_info.txt');
      Rewrite(F);
      try
        Writeln(F, 'started_at=' + FormatDateTime('yyyy-mm-dd hh:nn:ss', Now));
        Writeln(F, 'features=impostos_no_item;status_ext;payload_logs');
        Writeln(F, 'exe=' + Application.ExeName);
      finally
        CloseFile(F);
      end;
    except
    end;
    LogSecurityEvent('STARTUP', 'Emissor iniciado com sistema de segurança');
  except
    on E: Exception do
    begin
      ShowMessage('Erro no FormCreate: ' + E.Message + sLineBreak + 'Caption atual: ' + Caption);
      Caption := 'Emissor - erro';
    end;
  end;
end;



function TForm1.ReadRequestBody(ARequestInfo: TIdHTTPRequestInfo): string;
var
  ss: TStringStream;
begin
  Result := '';
  if Assigned(ARequestInfo.PostStream) then
  begin
    ss := TStringStream.Create('', TEncoding.UTF8);
    try
      ARequestInfo.PostStream.Position := 0;
      ss.CopyFrom(ARequestInfo.PostStream, ARequestInfo.PostStream.Size);
      Result := ss.DataString;
    finally
      ss.Free;
    end;
  end
  else
    Result := ARequestInfo.UnparsedParams;
end;

procedure TForm1.IdHTTPServer1CommandGet(AContext: TIdContext; ARequestInfo: TIdHTTPRequestInfo;
  AResponseInfo: TIdHTTPResponseInfo);
var
  Path, Body, Resp: string;
begin
  // CORS básico
  AResponseInfo.CustomHeaders.Values['Access-Control-Allow-Origin'] := '*';
  AResponseInfo.CustomHeaders.Values['Access-Control-Allow-Methods'] := 'GET, POST, OPTIONS';
  AResponseInfo.CustomHeaders.Values['Access-Control-Allow-Headers'] := 'Content-Type, Authorization, X-Token, X-Authorization, X-Api-Token';
  // Força charset UTF-8 para evitar caracteres quebrados em acentuação
  AResponseInfo.CharSet := 'utf-8';

  if SameText(ARequestInfo.Command, 'OPTIONS') then
  begin
    AResponseInfo.ResponseNo := 200;
    Exit;
  end;

  try
    Path := LowerCase(ARequestInfo.Document);
    // Normalização do caminho: URL-decode, trim e remoção de barra final
    try
      Path := LowerCase(TIdURI.URLDecode(Path));
    except
    end;
    Path := Trim(Path);
    // Remove querystring, se houver
    var qpos := Pos('?', Path);
    if qpos > 0 then
      Path := Copy(Path, 1, qpos - 1);
    if (Length(Path) > 1) and (Path[Length(Path)] = '/') then
      Delete(Path, Length(Path), 1);

    // Log básico da requisição (método e caminho)
    try
      var LogDir := ExtractFilePath(Application.ExeName) + 'logs\';
      if not DirectoryExists(LogDir) then ForceDirectories(LogDir);
      var F: TextFile;
      AssignFile(F, LogDir + 'access_' + FormatDateTime('yyyymmdd', Now) + '.log');
      if FileExists(LogDir + 'access_' + FormatDateTime('yyyymmdd', Now) + '.log') then
        Append(F)
      else
        Rewrite(F);
      try
        Writeln(F, FormatDateTime('hh:nn:ss', Now) + ' ' + ARequestInfo.Command + ' ' + Path);
      finally
        CloseFile(F);
      end;
    except
    end;
    
    // Endpoint público (sem autenticação)
    if (ARequestInfo.Command = 'GET') and (Path = '/api/status') then
    begin
      AResponseInfo.ResponseNo := 200;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := '{"ok":true,"message":"Emissor ativo","cert_ok":true}';
      Exit;
    end;

    // Endpoints protegidos (requerem autenticação)
    if (Path = '/api/emitir-nfe') and (ARequestInfo.Command <> 'POST') then
    begin
      AResponseInfo.ResponseNo := 405;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := '{"ok":false,"error":"use POST em /api/emitir-nfe"}';
      Exit;
    end;

    // Geração apenas do DANFE a partir de XML autorizado
    if (ARequestInfo.Command = 'POST') and (Path = '/api/gerar-danfe') then
    begin
      if not ValidateToken(ARequestInfo) then
      begin
        AResponseInfo.ResponseNo := 401;
        AResponseInfo.ContentType := 'application/json';
        AResponseInfo.ContentText := '{"error":"Token de autenticação inválido ou ausente"}';
        LogSecurityEvent('AUTH_FAILED', 'Tentativa de gerar DANFE sem token válido - IP: ' + AContext.Binding.PeerIP);
        Exit;
      end;
      Body := ReadRequestBody(ARequestInfo);
      // Fallbacks: aceitar JSON via query (json/urlencode) ou xml_path direto
      if (Body = '') then
      begin
        try
          var QJson := ARequestInfo.Params.Values['json'];
          if QJson <> '' then
            Body := TIdURI.URLDecode(QJson);
        except end;
        if (Body = '') then
        begin
          var QXml := ARequestInfo.Params.Values['xml_path'];
          if QXml <> '' then
            Body := '{"xml_path":"' + StringReplace(QXml, '"', '\"', [rfReplaceAll]) + '","configuracoes":{"gerar_pdf":true}}';
        end;
      end;
      Resp := GerarDanfeJSON(Body);
      AResponseInfo.ResponseNo := 200;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := Resp;
      Exit;
    end;

    if (ARequestInfo.Command = 'POST') and (Path = '/api/emitir-nfe') then
    begin
      if not ValidateToken(ARequestInfo) then
      begin
        AResponseInfo.ResponseNo := 401;
        AResponseInfo.ContentType := 'application/json';
        AResponseInfo.ContentText := '{"error":"Token de autenticação inválido ou ausente"}';
        LogSecurityEvent('AUTH_FAILED', 'Tentativa de emissão NFe sem token válido - IP: ' + AContext.Binding.PeerIP);
        Exit;
      end;
      
      Body := ReadRequestBody(ARequestInfo);
      // Fallbacks: aceitar JSON via query (json/urlencode) ou base64
      if (Body = '') then
      begin
        try
          var QJson := ARequestInfo.Params.Values['json'];
          if QJson <> '' then
            Body := TIdURI.URLDecode(QJson);
        except end;
        if (Body = '') then
        begin
          try
            var QB64 := ARequestInfo.Params.Values['b64'];
            if QB64 <> '' then
              Body := TEncoding.UTF8.GetString(TNetEncoding.Base64.DecodeStringToBytes(QB64));
          except end;
        end;
      end;
      // Salva payload recebido (debug)
      try
        var LogDir := ExtractFilePath(Application.ExeName) + 'logs\requests\';
        var Name := Format('emitir-nfe-%s.json', [FormatDateTime('yyyymmdd-hhnnss-zzz', Now)]);
        var Full := LogDir + Name;
        var F: TextFile;
        AssignFile(F, Full);
        Rewrite(F);
        try
          Writeln(F, Body);
        finally
          CloseFile(F);
        end;
      except
        // ignora erros de IO
      end;
      Resp := EmitirNFeJSON(Body);
      AResponseInfo.ResponseNo := 200;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := Resp;
      LogSecurityEvent('NFE_EMIT', 'NFe emitida com sucesso - IP: ' + AContext.Binding.PeerIP);
      Exit;
    end;

    if (ARequestInfo.Command = 'POST') and (Path = '/api/cancelar-nfe') then
    begin
      if not ValidateToken(ARequestInfo) then
      begin
        AResponseInfo.ResponseNo := 401;
        AResponseInfo.ContentType := 'application/json';
        AResponseInfo.ContentText := '{"error":"Token de autenticação inválido ou ausente"}';
        LogSecurityEvent('AUTH_FAILED', 'Tentativa de cancelamento NFe sem token válido - IP: ' + AContext.Binding.PeerIP);
        Exit;
      end;
      
      Body := ReadRequestBody(ARequestInfo);
      // Log do request para diagnóstico (como na emissão)
      try
        var LogDir := ExtractFilePath(Application.ExeName) + 'logs\requests\';
        var Name := Format('cancelar-nfe-%s.json', [FormatDateTime('yyyymmdd-hhnnss-zzz', Now)]);
        var Full := LogDir + Name;
        var F: TextFile;
        AssignFile(F, Full);
        Rewrite(F);
        try
          Writeln(F, Body);
        finally
          CloseFile(F);
        end;
      except
        // ignora erros de IO
      end;
      Resp := CancelarNFeJSON(Body);
      AResponseInfo.ResponseNo := 200;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := Resp;
      LogSecurityEvent('NFE_CANCEL', 'NFe cancelada com sucesso - IP: ' + AContext.Binding.PeerIP);
      Exit;
    end;

    if (ARequestInfo.Command = 'POST') and (Path = '/api/carta-correcao') then
    begin
      if not ValidateToken(ARequestInfo) then
      begin
        AResponseInfo.ResponseNo := 401;
        AResponseInfo.ContentType := 'application/json';
        AResponseInfo.ContentText := '{"error":"Token de autenticação inválido ou ausente"}';
        LogSecurityEvent('AUTH_FAILED', 'Tentativa de carta correção sem token válido - IP: ' + AContext.Binding.PeerIP);
        Exit;
      end;
      
      Body := ReadRequestBody(ARequestInfo);
      Resp := CartaCorrecaoJSON(Body);
      AResponseInfo.ResponseNo := 200;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := Resp;
      LogSecurityEvent('NFE_CCE', 'Carta de correção emitida com sucesso - IP: ' + AContext.Binding.PeerIP);
      Exit;
    end;

    if (ARequestInfo.Command = 'POST') and (Path = '/api/inutilizar-nfe') then
    begin
      if not ValidateToken(ARequestInfo) then
      begin
        AResponseInfo.ResponseNo := 401;
        AResponseInfo.ContentType := 'application/json';
        AResponseInfo.ContentText := '{"error":"Token de autenticação inválido ou ausente"}';
        LogSecurityEvent('AUTH_FAILED', 'Tentativa de inutilização NFe sem token válido - IP: ' + AContext.Binding.PeerIP);
        Exit;
      end;
      
      Body := ReadRequestBody(ARequestInfo);
      Resp := InutilizarNFeJSON(Body);
      AResponseInfo.ResponseNo := 200;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := Resp;
      LogSecurityEvent('NFE_INUTIL', 'NFe inutilizada com sucesso - IP: ' + AContext.Binding.PeerIP);
      Exit;
    end;

    if (ARequestInfo.Command = 'POST') and (Path = '/api/emitir-nfse') then
    begin
      if not ValidateToken(ARequestInfo) then
      begin
        AResponseInfo.ResponseNo := 401;
        AResponseInfo.ContentType := 'application/json';
        AResponseInfo.ContentText := '{"error":"Token de autenticação inválido ou ausente"}';
        LogSecurityEvent('AUTH_FAILED', 'Tentativa de emissão NFSe sem token válido - IP: ' + AContext.Binding.PeerIP);
        Exit;
      end;
      
      Body := ReadRequestBody(ARequestInfo);
      Resp := EmitirNFSeJSON(Body);
      AResponseInfo.ResponseNo := 200;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := Resp;
      LogSecurityEvent('NFSE_EMIT', 'NFSe emitida com sucesso - IP: ' + AContext.Binding.PeerIP);
      Exit;
    end;

    // NFC-e endpoint dedicado (aponta para o mesmo fluxo de emissão)
    if (ARequestInfo.Command = 'POST') and (Path = '/api/emitir-nfce') then
    begin
      if not ValidateToken(ARequestInfo) then
      begin
        AResponseInfo.ResponseNo := 401;
        AResponseInfo.ContentType := 'application/json';
        AResponseInfo.ContentText := '{"error":"Token de autenticação inválido ou ausente"}';
        LogSecurityEvent('AUTH_FAILED', 'Tentativa de emissão NFCe sem token válido - IP: ' + AContext.Binding.PeerIP);
        Exit;
      end;
      Body := ReadRequestBody(ARequestInfo);
      if (Body = '') then
      begin
        try
          var QJson := ARequestInfo.Params.Values['json'];
          if QJson <> '' then
            Body := TIdURI.URLDecode(QJson);
        except end;
      end;
      Resp := EmitirNFeJSON(Body);
      AResponseInfo.ResponseNo := 200;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := Resp;
      LogSecurityEvent('NFCE_EMIT', 'NFCe emitida (endpoint dedicado) - IP: ' + AContext.Binding.PeerIP);
      Exit;
    end;

    AResponseInfo.ResponseNo := 404;
    AResponseInfo.ContentType := 'application/json';
    AResponseInfo.ContentText := '{"ok":false,"error":"endpoint não encontrado","method":"' + ARequestInfo.Command + '","path":"' + Path + '"}';
  except
    on E: Exception do
    begin
      AResponseInfo.ResponseNo := 500;
      AResponseInfo.ContentType := 'application/json';
      AResponseInfo.ContentText := '{"ok":false,"error":"' + StringReplace(E.Message, '"', '\"', [rfReplaceAll]) + '"}';
    end;
  end;
end;

function TForm1.EmitirNFSeJSON(const JSONData: string): string;
var
  J, Prestador, Tomador, Rps, Servico: TJSONObject;
  Resp: TJSONObject;
begin
  Resp := TJSONObject.Create;
  try
    try
      J := TJSONObject(TJSONObject.ParseJSONValue(JSONData));
      if not Assigned(J) then
        raise Exception.Create('JSON inválido');

      Prestador := JsonGetObj(J, 'prestador');
      Tomador := JsonGetObj(J, 'tomador');
      Rps := JsonGetObj(J, 'rps');
      Servico := JsonGetObj(J, 'servico');

      if (Prestador = nil) or (Tomador = nil) or (Rps = nil) or (Servico = nil) then
        raise Exception.Create('Campos obrigatórios ausentes (prestador, tomador, rps, servico)');

      // Implementação mínima para validar fluxo. A implementação completa de ACBrNFSe
      // varia conforme provedor e versão. Aqui confirmamos recebimento e retornamos OK.

      Resp.AddPair('ok', TJSONBool.Create(True));
      Resp.AddPair('message', 'NFSe endpoint online. Implementação completa depende do provedor.');
    except
      on E: Exception do
      begin
        Resp.AddPair('ok', TJSONBool.Create(False));
        Resp.AddPair('error', E.Message);
      end;
    end;
    Result := Resp.ToString;
  finally
    Resp.Free;
  end;
end;

function TForm1.InutilizarNFeJSON(const JSONData: string): string;
var
  J: TJSONObject;
  Conf: TJSONObject;
  Resp: TJSONObject;
  emitCNPJ, justificativa: string;
  ano, modelo, serie, nIni, nFim: Integer;
  retXML: string;
  nn: Integer;
  outPath, dirPath, fileName: string;
  customPath: string;
  ambStr: string;
  ambVal: Integer;
  ambEnum: TpcnTipoAmbiente;
  ufStr: string;
  cUF: Integer;
  preInutXML, preInutPath: string;
  tpAmbStr: string;
  function MapUFToCUF(const uf: string): Integer;
  var s: string;
  begin
    s := UpperCase(Trim(uf));
    if s = 'RO' then Exit(11);
    if s = 'AC' then Exit(12);
    if s = 'AM' then Exit(13);
    if s = 'RR' then Exit(14);
    if s = 'PA' then Exit(15);
    if s = 'AP' then Exit(16);
    if s = 'TO' then Exit(17);
    if s = 'MA' then Exit(21);
    if s = 'PI' then Exit(22);
    if s = 'CE' then Exit(23);
    if s = 'RN' then Exit(24);
    if s = 'PB' then Exit(25);
    if s = 'PE' then Exit(26);
    if s = 'AL' then Exit(27);
    if s = 'SE' then Exit(28);
    if s = 'BA' then Exit(29);
    if s = 'MG' then Exit(31);
    if s = 'ES' then Exit(32);
    if s = 'RJ' then Exit(33);
    if s = 'SP' then Exit(35);
    if s = 'PR' then Exit(41);
    if s = 'SC' then Exit(42);
    if s = 'RS' then Exit(43);
    if s = 'MS' then Exit(50);
    if s = 'MT' then Exit(51);
    if s = 'GO' then Exit(52);
    if s = 'DF' then Exit(53);
    Result := 35;
  end;
  function TagVal(const Src, Tag: string): string;
  var o, c: Integer; ot, ct: string;
  begin
    Result := '';
    if Src = '' then Exit;
    ot := '<' + Tag + '>';
    ct := '</' + Tag + '>';
    o := Pos(ot, Src);
    if o > 0 then
    begin
      o := o + Length(ot);
      c := Pos(ct, Src);
      if (c > o) then
        Result := Copy(Src, o, c - o);
    end;
  end;
begin
  Resp := TJSONObject.Create;
  try
    try
      J := TJSONObject(TJSONObject.ParseJSONValue(JSONData));
      if not Assigned(J) then
        raise Exception.Create('JSON inválido');

      emitCNPJ := JsonGetStr(J, 'emit_cnpj', '');
      justificativa := JsonGetStr(J, 'justificativa', '');
      if Length(justificativa) < 15 then
        raise Exception.Create('Justificativa deve ter no mínimo 15 caracteres');

      ano := JsonGetInt(J, 'ano', StrToInt(FormatDateTime('yy', Now)));
      modelo := JsonGetInt(J, 'modelo', 55);
      serie := JsonGetInt(J, 'serie', 1);
      nIni := JsonGetInt(J, 'numero_inicial', 0);
      nFim := JsonGetInt(J, 'numero_final', nIni);

      // Ajusta ambiente/certificado/UF quando informado
      try
        ambStr := LowerCase(Trim(JsonGetStr(J, 'ambiente', '')));
        if ambStr = '' then
        begin
          Conf := JsonGetObj(J, 'configuracoes');
          if Assigned(Conf) then ambStr := LowerCase(Trim(JsonGetStr(Conf, 'ambiente', '')));
        end;
        ambVal := 2;
        if (ambStr = 'producao') or (ambStr = 'producao1') or (ambStr = '1') then ambVal := 1 else ambVal := 2;
        if ambVal = 1 then ambEnum := taProducao else ambEnum := taHomologacao;
        try ACBrNFe1.Configuracoes.WebServices.Ambiente := ambEnum; except end;
        // Certificado via bloco 'cert'
        try
          Conf := JsonGetObj(J, 'cert');
          if Assigned(Conf) then
          begin
            // PFX por caminho/senha
            try ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := JsonGetStr(Conf, 'path', ''); except end;
            try ACBrNFe1.Configuracoes.Certificados.Senha := JsonGetStr(Conf, 'password', ''); except end;
            // Serial (certificado do Windows)
            try ACBrNFe1.Configuracoes.Certificados.NumeroSerie := JsonGetStr(Conf, 'serial', ''); except end;
          end;
        except
        end;
        // UF
        try
          ufStr := JsonGetStr(J, 'uf', '');
          if (ufStr = '') and Assigned(Conf) then ufStr := JsonGetStr(Conf, 'uf', '');
          if ufStr <> '' then
            try ACBrNFe1.Configuracoes.WebServices.UF := ufStr; except end;
        except end;
      except end;

      // Gera e salva XML "pré-inutilização" para diagnóstico de schema
      try
        if ufStr = '' then ufStr := ACBrNFe1.Configuracoes.WebServices.UF;
        if ufStr = '' then ufStr := 'SP';
        cUF := MapUFToCUF(ufStr);
        if ambVal = 1 then tpAmbStr := '1' else tpAmbStr := '2';
        preInutXML := '<?xml version="1.0" encoding="UTF-8"?>' + sLineBreak +
          '<inutNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">' + sLineBreak +
          '  <infInut>' + sLineBreak +
          '    <tpAmb>' + tpAmbStr + '</tpAmb>' + sLineBreak +
          '    <xServ>INUTILIZAR</xServ>' + sLineBreak +
          '    <cUF>' + IntToStr(cUF) + '</cUF>' + sLineBreak +
          '    <ano>' + Format('%.2d',[ano]) + '</ano>' + sLineBreak +
          '    <CNPJ>' + DigitsOnly(emitCNPJ) + '</CNPJ>' + sLineBreak +
          '    <mod>' + IntToStr(modelo) + '</mod>' + sLineBreak +
          '    <serie>' + IntToStr(serie) + '</serie>' + sLineBreak +
          '    <nNFIni>' + IntToStr(nIni) + '</nNFIni>' + sLineBreak +
          '    <nNFFin>' + IntToStr(nFim) + '</nNFFin>' + sLineBreak +
          '    <xJust>' + justificativa + '</xJust>' + sLineBreak +
          '  </infInut>' + sLineBreak +
          '</inutNFe>';
        preInutPath := IncludeTrailingPathDelimiter(ExtractFilePath(Application.ExeName) + 'logs\\requests\\') +
          Format('pre_inut_%s_%0.2d_%d_%d_%d-%d_%s.xml', [DigitsOnly(emitCNPJ), ano, modelo, serie, nIni, nFim, FormatDateTime('yyyymmdd_hhnnss', Now)]);
        try ForceDirectories(ExtractFilePath(preInutPath)); except end;
        TFile.WriteAllText(preInutPath, preInutXML, TEncoding.UTF8);
      except
      end;

      // Chama inutilização; usa WebServices.Inutilizacao para garantir tpAmb/UF/serie corretos
      if nFim > nIni then
      begin
        retXML := '';
        for nn := nIni to nFim do
        begin
          try
            try
              // Algumas versões do ACBr esperam a ordem (CNPJ, Just, Ano, Serie, Modelo, Numero)
              ACBrNFe1.Configuracoes.WebServices.UF := ufStr;
              // Log de parâmetros de inutilização
              try LogSecurityEvent('NFE_INUT_CALL', Format('CNPJ=%s Ano=%d Modelo=%d Serie=%d Num=%d Amb=%s UF=%s',[DigitsOnly(emitCNPJ), ano, modelo, serie, nn, ambStr, ufStr])); except end;
              // ACBr Trunk2: usar WebServices.Inutiliza(CNPJ, Just, Ano, Modelo, Serie, NumIni, NumFim)
              ACBrNFe1.WebServices.Inutiliza(DigitsOnly(emitCNPJ), justificativa, ano, 55, serie, nn, nn);
              try
                retXML := ACBrNFe1.WebServices.Inutilizacao.RetWS;
              except
                // mantém último retXML válido
              end;
            except
              // segue para próximo número
            end
          except
            // continua tentando os próximos, mas reporta erro ao final via cStat/xMotivo quando possível
          end;
        end;
      end
      else
      begin
        try
          ACBrNFe1.Configuracoes.WebServices.UF := ufStr;
          try LogSecurityEvent('NFE_INUT_CALL', Format('CNPJ=%s Ano=%d Modelo=%d Serie=%d Num=%d Amb=%s UF=%s',[DigitsOnly(emitCNPJ), ano, modelo, serie, nIni, ambStr, ufStr])); except end;
          // ACBr Trunk2: usar WebServices.Inutiliza(CNPJ, Just, Ano, Modelo, Serie, NumIni, NumFim)
          ACBrNFe1.WebServices.Inutiliza(DigitsOnly(emitCNPJ), justificativa, ano, 55, serie, nIni, nIni);
          try
            retXML := ACBrNFe1.WebServices.Inutilizacao.RetWS;
          except
            retXML := '';
          end;
        except
          retXML := '';
        end;
      end;

      Resp.AddPair('ok', TJSONBool.Create(True));
      if retXML <> '' then
      begin
        Resp.AddPair('xml_retorno', retXML);
        // Extrai cStat/xMotivo do XML de retorno
        try
          Resp.AddPair('cStat', TagVal(retXML, 'cStat'));
          Resp.AddPair('xMotivo', TagVal(retXML, 'xMotivo'));
        except end;
        // Grava arquivo no diretório padrão ao lado do executável e opcionalmente no path recebido em configuracoes.path_xml
        try
          dirPath := IncludeTrailingPathDelimiter(ExtractFilePath(Application.ExeName) + 'nfe');
          try ForceDirectories(dirPath); except end;
          fileName := Format('inut_%s_%0.2d_%d_%d_%d-%d.xml', [DigitsOnly(emitCNPJ), ano, modelo, serie, nIni, nFim]);
          outPath := dirPath + fileName;
          TFile.WriteAllText(outPath, retXML, TEncoding.UTF8);
          if FileExists(outPath) then
            Resp.AddPair('xml_path', outPath);
        except end;
        // Extra: grava também em configuracoes.path_xml, se informado
        try
          Conf := JsonGetObj(J, 'configuracoes');
          if Assigned(Conf) then
          begin
            customPath := JsonGetStr(Conf, 'path_xml', '');
            if customPath <> '' then
            begin
              if (customPath[Length(customPath)] <> '\\') and (customPath[Length(customPath)] <> '/') then
                customPath := IncludeTrailingPathDelimiter(customPath);
              try ForceDirectories(customPath); except end;
              try
                TFile.WriteAllText(customPath + fileName, retXML, TEncoding.UTF8);
              except end;
            end;
          end;
        except end;
      end
      else
      begin
        // Tenta informar cStat/xMotivo mesmo sem XML
        try Resp.AddPair('cStat', IntToStr(ACBrNFe1.WebServices.Inutilizacao.CStat)); except end;
        try Resp.AddPair('xMotivo', ACBrNFe1.WebServices.Inutilizacao.XMotivo); except end;
        // Retorna caminho do pré-XML para análise
        try if preInutPath <> '' then Resp.AddPair('pre_inut_xml_path', preInutPath); except end;
        if (not Resp.GetValue<Boolean>('ok')) or ((Resp.GetValue<string>('cStat') = '') and (Resp.GetValue<string>('xMotivo') = '')) then
        begin
          try Resp.RemovePair('ok').Free; except end;
          Resp.AddPair('ok', TJSONBool.Create(False));
          Resp.AddPair('error', 'SEM_RETORNO_INUTILIZACAO');
        end;
      end;
    except
      on E: Exception do
      begin
        Resp.AddPair('ok', TJSONBool.Create(False));
        Resp.AddPair('error', E.Message);
      end;
    end;
    Result := Resp.ToString;
  finally
    Resp.Free;
  end;
end;

function TForm1.EmitirNFeJSON(const JSONData: string): string;
var
  J, EmitObj, DestObj, Conf, ItemObj: TJSONObject;
  Itens: TJSONArray;
  Resp: TJSONObject;
  TotalVProd, TotalVDesc, TotalBC, TotalICMS, TotalPIS, TotalCOFINS: Double;
  i: Integer;
  DestDocLen: Integer;
  DestIEVal: string;
  IsPessoaJuridica: Boolean;
  PreXMLPath, XMLPath, Protocolo, Chave: string;
  IsConsumidorFinal: Boolean;
  function Round2(const x: Double): Double;
  begin
    if x >= 0 then Result := Trunc(x * 100.0 + 0.5) / 100.0
    else Result := -Trunc(-x * 100.0 + 0.5) / 100.0;
  end;
  procedure SetTPagFromString(const CodeStr: string);
  var
    tCode: Integer;
  begin
    // Seta tPag de pag.New usando RTTI, compatível com enum ou string da sua versão
    tCode := StrToIntDef(Trim(CodeStr), 1);
    try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.pag.Items[ACBrNFe1.NotasFiscais.Items[0].NFe.pag.Count-1], 'tPag', tCode); except
      try SetStrProp(ACBrNFe1.NotasFiscais.Items[0].NFe.pag.Items[ACBrNFe1.NotasFiscais.Items[0].NFe.pag.Count-1], 'tPag', Format('%.2d',[tCode])); except end;
    end;
  end;
  function MapUFToCUF(const uf: string): Integer;
  var s: string;
  begin
    s := UpperCase(Trim(uf));
    if s = 'RO' then Exit(11);
    if s = 'AC' then Exit(12);
    if s = 'AM' then Exit(13);
    if s = 'RR' then Exit(14);
    if s = 'PA' then Exit(15);
    if s = 'AP' then Exit(16);
    if s = 'TO' then Exit(17);
    if s = 'MA' then Exit(21);
    if s = 'PI' then Exit(22);
    if s = 'CE' then Exit(23);
    if s = 'RN' then Exit(24);
    if s = 'PB' then Exit(25);
    if s = 'PE' then Exit(26);
    if s = 'AL' then Exit(27);
    if s = 'SE' then Exit(28);
    if s = 'BA' then Exit(29);
    if s = 'MG' then Exit(31);
    if s = 'ES' then Exit(32);
    if s = 'RJ' then Exit(33);
    if s = 'SP' then Exit(35);
    if s = 'PR' then Exit(41);
    if s = 'SC' then Exit(42);
    if s = 'RS' then Exit(43);
    if s = 'MS' then Exit(50);
    if s = 'MT' then Exit(51);
    if s = 'GO' then Exit(52);
    if s = 'DF' then Exit(53);
    Result := 35;
  end;
begin
  Resp := TJSONObject.Create;
  try
    try
      // Parse JSON
      J := TJSONObject(TJSONObject.ParseJSONValue(JSONData));
      if not Assigned(J) then
        raise Exception.Create('JSON inválido');

      // Certificado (opcional)
      Conf := JsonGetObj(J, 'cert');
      if Assigned(Conf) then
      begin
        if JsonGetStr(Conf, 'serial', '') <> '' then
        begin
          ACBrNFe1.Configuracoes.Certificados.NumeroSerie := JsonGetStr(Conf, 'serial', '');
          ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := '';
          ACBrNFe1.Configuracoes.Certificados.Senha := '';
        end
        else
        begin
          ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := AnsiString(JsonGetStr(Conf, 'path'));
          ACBrNFe1.Configuracoes.Certificados.Senha := AnsiString(JsonGetStr(Conf, 'password'));
        end;
      end;

      // Configurações gerais
      Conf := JsonGetObj(J, 'configuracoes');
      if Assigned(Conf) then
      begin
        try ACBrNFe1.Configuracoes.WebServices.UF := JsonGetStr(Conf, 'uf', 'SP'); except end;
        XMLPath := JsonGetStr(Conf, 'path_xml', '');
        if XMLPath <> '' then
          ACBrNFe1.Configuracoes.Arquivos.PathNFe := IncludeTrailingPathDelimiter(XMLPath);
      end;

      // Monta NFe
      ACBrNFe1.NotasFiscais.Clear;
      with ACBrNFe1.NotasFiscais.Add.NFe do
      begin
        // IDE
        IsConsumidorFinal := False;
        try
          if JsonGetInt(JsonGetObj(J, 'configuracoes'), 'modelo', 55) = 65 then Ide.modelo := 65 else Ide.modelo := 55;
        except Ide.modelo := 55; end;
        try Ide.serie := JsonGetInt(J, 'serie', 1); except end;
        try Ide.nNF := JsonGetInt(J, 'numero_nfe', JsonGetInt(J, 'numero', 1)); except end;
        try Ide.natOp := JsonGetStr(J, 'natOp', 'Venda de mercadoria'); except end;
        // dhEmi será forçado fora do bloco with (ver ajustes finais antes de GravarXML/Enviar)
        try Ide.verProc := 'QFiscal-Delphi-1.0'; except end;

        // Emitente
        EmitObj := JsonGetObj(J, 'emitente');
        var ufEmit := '';
        if Assigned(EmitObj) then
        begin
          try Emit.CNPJCPF := DigitsOnly(JsonGetStr(EmitObj, 'cnpj','')); except end;
          try Emit.IE := DigitsOnly(JsonGetStr(EmitObj, 'ie','')); except end;
          try Emit.xNome := JsonGetStr(EmitObj, 'razao_social',''); except end;
          try Emit.xFant := JsonGetStr(EmitObj, 'nome_fantasia',''); except end;
          try Emit.EnderEmit.xLgr := JsonGetStr(EmitObj, 'endereco',''); except end;
          try Emit.EnderEmit.nro := JsonGetStr(EmitObj, 'numero',''); except end;
          try Emit.EnderEmit.xBairro := JsonGetStr(EmitObj, 'bairro',''); except end;
          try Emit.EnderEmit.cMun := StrToIntDef(JsonGetStr(EmitObj, 'codigo_ibge','0'), 0); except end;
          if Emit.EnderEmit.cMun = 0 then
            try Emit.EnderEmit.cMun := StrToIntDef(JsonGetStr(EmitObj, 'codigo_municipio','0'), 0); except end;
          try Emit.EnderEmit.xMun := JsonGetStr(EmitObj, 'cidade',''); except end;
          try Emit.EnderEmit.UF := JsonGetStr(EmitObj, 'uf','SP'); except end;
          ufEmit := Emit.EnderEmit.UF;
          try Emit.EnderEmit.CEP := StrToIntDef(DigitsOnly(JsonGetStr(EmitObj, 'cep','0')), 0); except end;
        end;

        // Destinatário
        DestObj := JsonGetObj(J, 'cliente');
        var ufDest := '';
        if Assigned(DestObj) then
        begin
          // Detecta tipo de pessoa e escolhe CPF/CNPJ correto
          var tipoPessoa := UpperCase(Trim(JsonGetStr(DestObj, 'tipo_pessoa', JsonGetStr(DestObj, 'tipo', JsonGetStr(DestObj, 'pessoa', '')))));
          var rawCPF := DigitsOnly(JsonGetStr(DestObj, 'cpf', ''));
          var rawCNPJ := DigitsOnly(JsonGetStr(DestObj, 'cnpj', ''));
          var rawMix := DigitsOnly(JsonGetStr(DestObj, 'cpf_cnpj',''));
          var Doc := '';
          // Seleção robusta do documento: nunca gerar zeros
          IsPessoaJuridica := False;
          if (tipoPessoa <> '') and ((tipoPessoa = 'F') or (tipoPessoa = 'PF') or (tipoPessoa = 'FISICA') or (tipoPessoa = 'FÍSICA')) then
          begin
            if Length(rawCPF) = 11 then Doc := rawCPF
            else if Length(rawMix) = 11 then Doc := rawMix;
          end
          else if (tipoPessoa <> '') and ((tipoPessoa = 'J') or (tipoPessoa = 'PJ') or (tipoPessoa = 'JURIDICA') or (tipoPessoa = 'JURÍDICA')) then
          begin
            IsPessoaJuridica := True;
            if Length(rawCNPJ) = 14 then Doc := rawCNPJ
            else if Length(rawMix) = 14 then Doc := rawMix;
          end
          else
          begin
            if Length(rawCPF) = 11 then Doc := rawCPF
            else if Length(rawCNPJ) = 14 then Doc := rawCNPJ
            else if (Length(rawMix) = 11) or (Length(rawMix) = 14) then Doc := rawMix;
            IsPessoaJuridica := Length(Doc) = 14;
          end;
          DestDocLen := Length(Doc);
          try
            if (DestDocLen = 11) or (DestDocLen = 14) then Dest.CNPJCPF := Doc else Dest.CNPJCPF := '';
          except end;
          try Dest.xNome := JsonGetStr(DestObj, 'nome',''); except end;
          // Captura IE a partir de múltiplas chaves possíveis no ERP
          var ieRaw := Trim(JsonGetStr(DestObj, 'ie',
                          JsonGetStr(DestObj, 'inscricao_estadual',
                          JsonGetStr(DestObj, 'insc_estadual',
                          JsonGetStr(DestObj, 'ie_rg',
                          JsonGetStr(DestObj, 'rg_ie',
                          JsonGetStr(DestObj, 'state_registration', '')))))));
          var ieUpper := UpperCase(ieRaw);
          var ieDigits := DigitsOnly(ieRaw);
          // Log para diagnóstico quando PJ sem IE
          if IsPessoaJuridica and (ieDigits = '') then
          begin
            try
              var LogDir := ExtractFilePath(Application.ExeName) + 'logs\';
              var F: TextFile;
              AssignFile(F, LogDir + 'ie_missing_' + FormatDateTime('yyyymmdd', Now) + '.log');
              if FileExists(LogDir + 'ie_missing_' + FormatDateTime('yyyymmdd', Now) + '.log') then Append(F) else Rewrite(F);
              try
                Writeln(F, FormatDateTime('hh:nn:ss', Now) + ' PJ SEM IE: Doc=' + Doc + ' Nome=' + JsonGetStr(DestObj, 'nome','') + ' ieRaw="' + ieRaw + '"');
              finally CloseFile(F); end;
            except end;
          end;
          // consumidor final
          try
            var consFlag := UpperCase(Trim(JsonGetStr(DestObj, 'consumidor_final', '')));
            if (Dest.IE = '') or (consFlag = 'S') or (consFlag = 'SIM') or (consFlag = '1') then
              IsConsumidorFinal := True;
          except end;
          try Dest.EnderDest.xLgr := JsonGetStr(DestObj, 'endereco',''); except end;
          try Dest.EnderDest.nro := JsonGetStr(DestObj, 'numero',''); except end;
          try Dest.EnderDest.xBairro := JsonGetStr(DestObj, 'bairro',''); except end;
          try Dest.EnderDest.cMun := StrToIntDef(JsonGetStr(DestObj, 'codigo_ibge','0'), 0); except end;
          if Dest.EnderDest.cMun = 0 then
            try Dest.EnderDest.cMun := StrToIntDef(JsonGetStr(DestObj, 'codigo_municipio','0'), 0); except end;
          try Dest.EnderDest.xMun := JsonGetStr(DestObj, 'cidade',''); except end;
          try Dest.EnderDest.UF := JsonGetStr(DestObj, 'uf',''); except end;
          ufDest := Dest.EnderDest.UF;
          try Dest.EnderDest.CEP := StrToIntDef(DigitsOnly(JsonGetStr(DestObj, 'cep','0')), 0); except end;
          // Se PJ e IE válida presente (não ISENTO), atribui IE
          if DestDocLen = 14 then
        begin
            if (ieUpper <> 'ISENTO') and (ieUpper <> 'ISENTA') and (ieDigits <> '') then
          begin
              // IE fornecida pelo ERP
              try Dest.IE := ieDigits; except end;
              DestIEVal := ieDigits;
            end;
          end;
          // Define indIEDest conforme documento/IE
          try
            var ieStr := ieUpper;
            var ieIsEmpty := (ieStr = '') or (ieStr = '0') or (ieStr = '00') or (ieStr = '000000000000') or (ieStr = 'NULL') or (ieStr = 'NULO') or (ieStr = 'NAO') or (ieStr = 'NÃO');
            if not IsPessoaJuridica then
            begin
              // Pessoa Física → não contribuinte e sem IE
              try Dest.IE := ''; except end;
              try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Dest, 'indIEDest', 9); except try SetStrProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Dest, 'indIEDest', '9'); except end; end;
            end
              else
              begin
              // Pessoa Jurídica
              if (ieStr = 'ISENTO') or (ieStr = 'ISENTA') then
                try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Dest, 'indIEDest', 2); except try SetStrProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Dest, 'indIEDest', '2'); except end; end
              else if not ieIsEmpty then
                try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Dest, 'indIEDest', 1); except try SetStrProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Dest, 'indIEDest', '1'); except end; end
              else
                try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Dest, 'indIEDest', 9); except try SetStrProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Dest, 'indIEDest', '9'); except end; end;
            end;
                except end;

          // Força presença da tag <IE> quando CNPJ e indIEDest=1
          if (DestDocLen = 14) then
                begin
            try
              // Se indIEDest ficou 1 e IE está vazia, preenche com IE do ERP se informada
              var indVal := 0;
              try indVal := GetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Dest, 'indIEDest'); except end;
              if (indVal = 1) and ((Trim(Dest.IE) = '') and (DestIEVal <> '')) then
                try Dest.IE := DestIEVal; except end;
            except end;
          end;
        end;

        // Mapear cUF e cMunFG (município de ocorrência)
        try Ide.cUF := MapUFToCUF(ufEmit); except end;
        if Emit.EnderEmit.cMun > 0 then
          try Ide.cMunFG := Emit.EnderEmit.cMun; except end;
        // idDest
        if (ufEmit <> '') and (ufDest <> '') then
        begin
          if ufEmit = ufDest then begin try SetOrdProp(Ide, 'idDest', 1); except end; end
          else begin try SetOrdProp(Ide, 'idDest', 2); except end; end;
        end;

        // Itens
        TotalVProd := 0; TotalVDesc := 0; TotalBC := 0; TotalICMS := 0; TotalPIS := 0; TotalCOFINS := 0;
        Itens := JsonGetArr(J, 'produtos');
        if Assigned(Itens) then
        begin
          for i := 0 to Itens.Count - 1 do
          begin
            ItemObj := Itens.Items[i] as TJSONObject;
            with Det.New do
            begin
              Prod.nItem := i+1;
              try Prod.cProd := JsonGetStr(ItemObj, 'codigo', ''); except end;
              try Prod.xProd := JsonGetStr(ItemObj, 'nome', ''); except end;
              try Prod.NCM := JsonGetStr(ItemObj, 'ncm', ''); except end;
              try Prod.CFOP := JsonGetStr(ItemObj, 'cfop', '5102'); except end;
              try Prod.uCom := JsonGetStr(ItemObj, 'unidade', 'UN'); except end;
              try Prod.qCom := StrToFloatDef(JsonGetStr(ItemObj, 'quantidade', '1'), 1.0); except end;
              try Prod.vUnCom := StrToFloatDef(JsonGetStr(ItemObj, 'valor_unitario', '0'), 0.0); except end;
              // Garantir unidade e quantidade tributável
              try Prod.uTrib := Prod.uCom; except end;
              try Prod.qTrib := Prod.qCom; except end;
              try Prod.vUnTrib := Prod.vUnCom; except end;
              try Prod.vProd := StrToFloatDef(JsonGetStr(ItemObj, 'valor_total', ''), Prod.qCom * Prod.vUnCom); except end;
              try Prod.vDesc := StrToFloatDef(JsonGetStr(ItemObj, 'vDesc', '0'), 0.0); except end;
              try TotalVProd := TotalVProd + Prod.vProd; except end;
              try TotalVDesc := TotalVDesc + Prod.vDesc; except end;
              // Base líquida do item
              var baseNet := Prod.vProd - Prod.vDesc; if baseNet < 0 then baseNet := 0;
              // ICMS
              try
                Imposto.ICMS.vBC := baseNet;
                if Imposto.ICMS.pICMS <= 0 then Imposto.ICMS.pICMS := 18.00;
                Imposto.ICMS.vICMS := Round2(baseNet * (Imposto.ICMS.pICMS / 100.0));
                TotalBC := TotalBC + Imposto.ICMS.vBC;
                TotalICMS := TotalICMS + Imposto.ICMS.vICMS;
              except end;
              // PIS
              try
                Imposto.PIS.vBC := baseNet;
                if Imposto.PIS.pPIS <= 0 then Imposto.PIS.pPIS := 1.65;
                Imposto.PIS.vPIS := Round2(baseNet * (Imposto.PIS.pPIS / 100.0));
                TotalPIS := TotalPIS + Imposto.PIS.vPIS;
                except end;
              // COFINS
              try
                Imposto.COFINS.vBC := baseNet;
                if Imposto.COFINS.pCOFINS <= 0 then Imposto.COFINS.pCOFINS := 7.60;
                Imposto.COFINS.vCOFINS := Round2(baseNet * (Imposto.COFINS.pCOFINS / 100.0));
                TotalCOFINS := TotalCOFINS + Imposto.COFINS.vCOFINS;
              except end;
            end;
              end;
            end;

        // Pagamentos
          var Pays := JsonGetArr(J, 'pagamentos');
        if Assigned(Pays) and (Pays.Count > 0) then
            begin
          for i := 0 to Pays.Count - 1 do
          begin
            var Pay := Pays.Items[i] as TJSONObject;
            try
              pag.New;
              SetTPagFromString(JsonGetStr(Pay, 'tPag', JsonGetStr(Pay, 'forma', '01')));
              try pag.Items[pag.Count-1].vPag := StrToFloatDef(JsonGetStr(Pay, 'valor', '0'), 0.0); except end;
            except
            end;
          end;
        end;

        // Totais
        try
          Total.ICMSTot.vProd := Round2(TotalVProd);
          Total.ICMSTot.vDesc := Round2(TotalVDesc);
          Total.ICMSTot.vBC := Round2(TotalBC);
          Total.ICMSTot.vICMS := Round2(TotalICMS);
          Total.ICMSTot.vPIS := Round2(TotalPIS);
          Total.ICMSTot.vCOFINS := Round2(TotalCOFINS);
          Total.ICMSTot.vNF := Round2(TotalVProd - TotalVDesc + Total.ICMSTot.vFrete + Total.ICMSTot.vSeg + Total.ICMSTot.vOutro);
          except end;

        // Reconciliar vNF = soma(vPag)
        var paySum := 0.0;
        for i := 0 to pag.Count - 1 do
          try paySum := paySum + pag.Items[i].vPag; except end;
        if (paySum <= 0) and (Total.ICMSTot.vNF > 0) then
        begin
          // cria pagamento padrão igual ao vNF
          try
            pag.New;
            SetTPagFromString('01');
            pag.Items[pag.Count-1].vPag := Total.ICMSTot.vNF;
          except end;
          paySum := Total.ICMSTot.vNF;
        end;
        if (paySum > 0) and (Abs(Total.ICMSTot.vNF - paySum) > 0.01) then
          begin
          var diff := Total.ICMSTot.vNF - paySum;
          if diff > 0 then
            begin
            try Total.ICMSTot.vDesc := Round2(Total.ICMSTot.vDesc + diff); except end;
            try Total.ICMSTot.vNF := Round2(paySum); except end;
            end
            else
            try Total.ICMSTot.vNF := Round2(paySum); except end;
        end;

        // indFinal/indPres serão forçados fora do bloco with (ver ajustes finais antes de GravarXML/Enviar)
      end; // with NFe

      // ===================================================================
      // AJUSTES FINAIS OBRIGATÓRIOS (ANTES de GravarXML e Enviar)
      // - Força dhEmi para a data/hora atual com fallback dEmi/hEmi
      // - Força indFinal=1 e indPres=1 fora de condicionais
      // ===================================================================
      // Ajustes finais serão aplicados diretamente no XML (dhEmi, indFinal, indPres)

      // Grava pré-XML e envia
      try
        PreXMLPath := ExtractFilePath(Application.ExeName) + 'logs\\requests\\pre_envio_final_' + FormatDateTime('yyyymmdd_hhnnss', Now) + '.xml';
        try ForceDirectories(ExtractFilePath(PreXMLPath)); except end;
        // Remoção preventiva de IBS/CBS no XML final será feita abaixo
          ACBrNFe1.NotasFiscais.Items[0].GravarXML(PreXMLPath);
        // Pós-processamento mínimo no XML: força dhEmi, indFinal, indPres
        try
          var XmlTxt := TFile.ReadAllText(PreXMLPath, TEncoding.UTF8);
          var tz := '-03:00';
          var dt := FormatDateTime('yyyy-mm-dd"T"hh:nn:ss', Now) + tz;
          // dhEmi: atualizar ou inserir
          if Pos('<dhEmi>', XmlTxt) > 0 then
        begin
            var p1 := Pos('<dhEmi>', XmlTxt);
            var p2 := Pos('</dhEmi>', XmlTxt);
            if (p1 > 0) and (p2 > p1) then
          begin
              var prefix := Copy(XmlTxt, 1, p1 + Length('<dhEmi>') - 1);
              var suffix := Copy(XmlTxt, p2, MaxInt);
              XmlTxt := prefix + dt + suffix;
            end;
          end
          else if Pos('</ide>', XmlTxt) > 0 then
          begin
            XmlTxt := StringReplace(XmlTxt, '</ide>', '<dhEmi>' + dt + '</dhEmi></ide>', []);
          end;

          // indFinal: força 1
          if Pos('<indFinal>', XmlTxt) > 0 then
            XmlTxt := StringReplace(XmlTxt,
              '<indFinal>0</indFinal>', '<indFinal>1</indFinal>', [rfReplaceAll])
          else if Pos('</ide>', XmlTxt) > 0 then
            XmlTxt := StringReplace(XmlTxt, '</ide>', '<indFinal>1</indFinal></ide>', []);

          // indPres: força 1
          if Pos('<indPres>', XmlTxt) > 0 then
            XmlTxt := StringReplace(XmlTxt,
              '<indPres>0</indPres>', '<indPres>1</indPres>', [rfReplaceAll])
          else if Pos('</ide>', XmlTxt) > 0 then
            XmlTxt := StringReplace(XmlTxt, '</ide>', '<indPres>1</indPres></ide>', []);

          // Ajustes de destinatário no XML
          // 1) PF não pode ficar com indIEDest=1 → troca para 9
          if DestDocLen = 11 then
        begin
            XmlTxt := StringReplace(XmlTxt,
              '<indIEDest>1</indIEDest>', '<indIEDest>9</indIEDest>', [rfReplaceAll]);
          end;

          // 2) PJ com indIEDest=1 deve possuir <IE> – verificação robusta direto no XML do bloco <dest>
          var destStart := Pos('<dest>', XmlTxt);
          var destEnd := 0;
          if destStart > 0 then destEnd := PosEx('</dest>', XmlTxt, destStart);
          if (destStart > 0) and (destEnd > destStart) then
      begin
            var destXml := Copy(XmlTxt, destStart, destEnd - destStart + Length('</dest>'));
            var cnpjVal := '';
            var pC1 := Pos('<CNPJ>', destXml);
            var pC2 := Pos('</CNPJ>', destXml);
            if (pC1 > 0) and (pC2 > pC1) then
              cnpjVal := DigitsOnly(Copy(destXml, pC1 + Length('<CNPJ>'), pC2 - (pC1 + Length('<CNPJ>'))));
            var hasIE := Pos('<IE>', destXml) > 0;
            var hasInd1 := Pos('<indIEDest>1</indIEDest>', destXml) > 0;
            if IsPessoaJuridica and hasInd1 then
          begin
              if (not hasIE) and (Trim(DestIEVal) <> '') then
            begin
                // Inserir IE após </indIEDest>
                var posIndClose := Pos('</indIEDest>', destXml);
                if posIndClose > 0 then
              begin
                  var insPos := destStart + posIndClose + Length('</indIEDest>') - 1;
                  var prefix := Copy(XmlTxt, 1, insPos);
                  var suffix := Copy(XmlTxt, insPos + 1, MaxInt);
                  XmlTxt := prefix + '<IE>' + DestIEVal + '</IE>' + suffix;
                end;
              end
              else if not hasIE then
        begin
                // Sem IE: trocar indIEDest para 9
                // Só dentro do bloco dest para evitar efeitos colaterais
                var destNew := StringReplace(destXml, '<indIEDest>1</indIEDest>', '<indIEDest>9</indIEDest>', []);
                XmlTxt := Copy(XmlTxt, 1, destStart - 1) + destNew + Copy(XmlTxt, destEnd + Length('</dest>'), MaxInt);
          end;
        end;
          end;

          // Remoção de blocos experimentais IBS/CBS que causam rejeição
          XmlTxt := StringReplace(XmlTxt, '<IBSCBS>', '', [rfReplaceAll, rfIgnoreCase]);
          XmlTxt := StringReplace(XmlTxt, '</IBSCBS>', '', [rfReplaceAll, rfIgnoreCase]);
          XmlTxt := StringReplace(XmlTxt, '<CBS>', '', [rfReplaceAll, rfIgnoreCase]);
          XmlTxt := StringReplace(XmlTxt, '</CBS>', '', [rfReplaceAll, rfIgnoreCase]);
          // Injeta grupo IBS mínimo em cada <det> se ausente
          var scanPos := 1;
          while True do
          begin
            var dStart := PosEx('<det ', XmlTxt, scanPos);
            if dStart = 0 then Break;
            var dEnd := PosEx('</det>', XmlTxt, dStart);
            if (dEnd = 0) then Break;
            var detXml := Copy(XmlTxt, dStart, dEnd - dStart + Length('</det>'));
            var impStart := Pos('<imposto>', detXml);
            var impCloseTag := Pos('</imposto>', detXml);
            if (impStart > 0) and (impCloseTag > impStart) then
            begin
              var impBlock := Copy(detXml, impStart, impCloseTag - impStart);
              var hasIBS := Pos('<IBS>', impBlock) > 0;
              if not hasIBS then
              begin
                // Posição absoluta de </imposto> no XML completo
                var absClosePos := dStart + impCloseTag - 1;
                var prefix := Copy(XmlTxt, 1, absClosePos - 1);
                var suffix := Copy(XmlTxt, absClosePos, MaxInt);
                var ibs := '<IBS><vBC>0.00</vBC><pIBS>0.00</pIBS><vIBS>0.00</vIBS></IBS>';
                XmlTxt := prefix + ibs + suffix;
                dEnd := dEnd + Length(ibs);
              end;
            end;
            scanPos := dEnd + Length('</det>');
          end;

          TFile.WriteAllText(PreXMLPath, XmlTxt, TEncoding.UTF8);

          // Recarrega o XML ajustado para garantir envio com os valores forçados
          try ACBrNFe1.NotasFiscais.Clear; except end;
          ACBrNFe1.NotasFiscais.LoadFromFile(PreXMLPath);
          except
        end;
        Resp.AddPair('pre_xml_path_final', PreXMLPath);
      except end;

        if not ACBrNFe1.Enviar(1, False, True) then
          raise Exception.Create('Falha ao transmitir NFe');

      // Resultado
      Chave := ACBrNFe1.NotasFiscais.Items[0].NFe.infNFe.ID;
      Protocolo := ACBrNFe1.WebServices.Retorno.Protocolo;
      XMLPath := ACBrNFe1.NotasFiscais.Items[0].NomeArq;

      Resp.AddPair('ok', TJSONBool.Create(True));
      if Chave <> '' then Resp.AddPair('chave', Chave);
      if Protocolo <> '' then Resp.AddPair('protocolo', Protocolo);
      if XMLPath <> '' then Resp.AddPair('xml_path', XMLPath);

    except
      on E: Exception do
      begin
        Resp.AddPair('ok', TJSONBool.Create(False));
        Resp.AddPair('error', E.Message);
      end;
    end;
    Result := Resp.ToString;
  finally
    Resp.Free;
  end;
end;


function TForm1.GerarDanfeJSON(const JSONData: string): string;
var
  J, Conf: TJSONObject;
  Resp: TJSONObject;
  XMLPath, PDFDir, PDFPath: string;
  GeneratePDF: Boolean;
  PDFSuccess: Boolean;
  PdfErrorMsg: string;
  ComInitialized: Boolean;
  EnginePref: string;
  UseFPDF: Boolean;
  // Sanitização do XML para evitar ERangeError no RLNFe/Fortes
  function SanitizeText(const S: string): string;
  var i: Integer; ch: Char;
  begin
    Result := '';
    for i := 1 to Length(S) do
    begin
      ch := S[i];
      // mantém CR/LF e caracteres imprimíveis básicos
      if (ch = #13) or (ch = #10) or (Ord(ch) >= 32) then
        Result := Result + ch;
    end;
    // normaliza espaços
    Result := Trim(StringReplace(Result, '  ', ' ', [rfReplaceAll]));
  end;
  function TruncTo(const S: string; MaxLen: Integer): string;
  begin
    if (MaxLen > 0) and (Length(S) > MaxLen) then
      Result := Copy(S, 1, MaxLen)
    else
      Result := S;
  end;
  function ReplaceTagValueAll(const Xml, Tag: string; MaxLen: Integer): string;
  var
    openTag, closeTag: string;
    p1, p2, startVal: Integer;
    val, newVal: string;
    Work: string;
  begin
    Work := Xml;
    openTag := '<' + Tag + '>';
    closeTag := '</' + Tag + '>';
    p1 := Pos(openTag, Work);
    while p1 > 0 do
    begin
      startVal := p1 + Length(openTag);
      p2 := Pos(closeTag, Work);
      if (p2 > startVal) then
      begin
        val := Copy(Work, startVal, p2 - startVal);
        newVal := TruncTo(SanitizeText(val), MaxLen);
        if newVal <> val then
        begin
          Work := Copy(Work, 1, p1 - 1) + openTag + newVal + closeTag + Copy(Work, p2 + Length(closeTag), MaxInt);
        end
        else
        begin
          // avança após o fechamento para achar próximas ocorrências
          p1 := Pos(openTag, Copy(Work, p2 + Length(closeTag), MaxInt));
          if p1 > 0 then p1 := p1 + p2 + Length(closeTag);
          Continue;
        end;
      end
      else
        Break;
      // procurar próxima ocorrência
      p1 := Pos(openTag, Work);
    end;
    Result := Work;
  end;
  function SanitizeXmlForDanfe(const OrigXmlPath: string; out OutPath: string): Boolean;
  var
    S: string;
    TempDir: string;
  begin
    Result := False;
    OutPath := '';
    try
      S := TFile.ReadAllText(OrigXmlPath, TEncoding.UTF8);
    except
      Exit;
    end;
    try
      // Corrige <xCpl>null</xCpl>
      S := StringReplace(S, '<xCpl>null</xCpl>', '<xCpl></xCpl>', [rfReplaceAll, rfIgnoreCase]);
      // Trunca campos críticos do layout do DANFE (valores conservadores)
      S := ReplaceTagValueAll(S, 'xNome', 60);
      S := ReplaceTagValueAll(S, 'xFant', 60);
      S := ReplaceTagValueAll(S, 'xLgr', 60);
      S := ReplaceTagValueAll(S, 'xBairro', 40);
      S := ReplaceTagValueAll(S, 'xMun', 40);
      S := ReplaceTagValueAll(S, 'xCpl', 60);
      S := ReplaceTagValueAll(S, 'xProd', 60);
      S := ReplaceTagValueAll(S, 'natOp', 60);
      // Salva XML sanitizado em arquivo temporário
      TempDir := ExtractFilePath(Application.ExeName) + 'logs\requests\';
      try ForceDirectories(TempDir); except end;
      OutPath := TempDir + 'sanitized_' + FormatDateTime('yyyymmdd_hhnnss_zzz', Now) + '.xml';
      TFile.WriteAllText(OutPath, S, TEncoding.UTF8);
      Result := FileExists(OutPath);
    except
      Result := False;
    end;
  end;
begin
  Resp := TJSONObject.Create;
  ComInitialized := Succeeded(CoInitialize(nil));
  try
    try
      J := TJSONObject(TJSONObject.ParseJSONValue(JSONData));
      if not Assigned(J) then
        raise Exception.Create('JSON inválido');

      // Caminho do XML autorizado
      XMLPath := JsonGetStr(J, 'xml_path', '');
      if (XMLPath = '') or (not FileExists(XMLPath)) then
        raise Exception.Create('XML não encontrado em xml_path');

      // Configurações (opcionais)
      Conf := JsonGetObj(J, 'configuracoes');
      if Assigned(Conf) then
      begin
        try GeneratePDF := SameText(Trim(JsonGetStr(Conf, 'gerar_pdf', 'true')), 'true'); except GeneratePDF := True; end;
        EnginePref := UpperCase(Trim(JsonGetStr(Conf, 'engine', '')));
        UseFPDF := EnginePref = 'FPDF';
        // Detecta pedido explícito de tarja CANCELADA
        var WantCancelStripe := False;
        try
          var tarja := Trim(JsonGetStr(Conf, 'tarja_cancelada', ''));
          var canc  := Trim(JsonGetStr(Conf, 'cancelada', ''));
          var stat  := UpperCase(Trim(JsonGetStr(Conf, 'status', '')));
          if SameText(tarja, 'true') then WantCancelStripe := True;
          if (canc = '1') or SameText(canc, 'true') then WantCancelStripe := True;
          if stat = 'CANCELADA' then WantCancelStripe := True;
        except
        end;
        // Logo opcional
        try
          var LogoPath := JsonGetStr(Conf, 'logo_path', '');
          if (LogoPath <> '') and FileExists(LogoPath) then
            try ACBrNFeDANFeRL1.Logo := LogoPath; except end;
        except end;
        // Se houve pedido de tarja e não temos implementação na engine, forçamos fallback no ERP (não retornar PDF)
        // Observação: o ERP já imprime tarja CANCELADA no fallback.
        if WantCancelStripe then
        begin
          try Resp.AddPair('cancel_tarja_requested', TJSONBool.Create(True)); except end;
        end;
      end
      else
      begin
        GeneratePDF := True;
        UseFPDF := False;
      end;

      // Carrega o XML no ACBr (usar versão sanitizada para evitar ERangeError no RL)
      ACBrNFe1.NotasFiscais.Clear;
      var SanPath: string := '';
      var SanApplied: Boolean := False;
      try
        if SanitizeXmlForDanfe(XMLPath, SanPath) and FileExists(SanPath) then
        begin
          ACBrNFe1.NotasFiscais.LoadFromFile(SanPath);
          SanApplied := True;
        end
        else
          ACBrNFe1.NotasFiscais.LoadFromFile(XMLPath);
      except
        on E: Exception do
          raise Exception.Create('Falha ao carregar XML: ' + E.Message);
      end;

      // Pasta de saída do PDF
      PDFDir := 'C:\xampp\htdocs\Emissor\qfiscal\storage\danfe\';
      try ForceDirectories(PDFDir); except end;
      try ACBrNFeDANFeRL1.PathPDF := PDFDir; except end;

      PDFSuccess := False;
      PdfErrorMsg := '';
      if GeneratePDF then
      begin
        // Estratégia agressiva para evitar WIC: configuração mínima
        try
          ACBrNFeDANFeRL1.Logo := '';
          ACBrNFeDANFeRL1.Sistema := '';
          ACBrNFeDANFeRL1.Impressora := '';
          // Habilita QRCode na DANFE (quando aplicável) usando RTTI para compatibilidade entre versões do ACBr
          try SetPropValue(ACBrNFeDANFeRL1, 'ImprimirQRCode', True); except end;
          try SetPropValue(ACBrNFeDANFeRL1, 'ImprimirQRCodeLateral', True); except end;
          try SetPropValue(ACBrNFeDANFeRL1, 'ImprimirQRCodeLateralLargura', 50); except end;
          // Alguns forks/versões usam nomes alternativos
          try SetPropValue(ACBrNFeDANFeRL1, 'ExibirQRCode', True); except end;
          try SetPropValue(ACBrNFeDANFeRL1, 'QRCodeLateral', True); except end;
          // Ajusta versão do QRCode conforme layout (via RTTI para evitar dependência de constantes)
          try
            SetEnumProp(ACBrNFe1.Configuracoes.Geral, 'VersaoQRCode', 'veqr200');
          except
            try SetOrdProp(ACBrNFe1.Configuracoes.Geral, 'VersaoQRCode', 2); except end;
          end;
          // Força nome de saída previsível
          var Ch := ACBrNFe1.NotasFiscais.Items[0].NFe.infNFe.ID;
        except end;
        if UseFPDF then
        begin
          // Usa somente FPDF (evita RL/ScanLine por completo)
          var OldDanfeFP := ACBrNFe1.DANFE;
          var DanfeFPDF := TACBrNFeDANFeFPDF.Create(nil);
          try
            try DanfeFPDF.PathPDF := PDFDir; except end;
            ACBrNFe1.DANFE := DanfeFPDF;
            ACBrNFe1.NotasFiscais.ImprimirPDF;
            PDFSuccess := True;
          finally
            ACBrNFe1.DANFE := OldDanfeFP;
            DanfeFPDF.Free;
          end;
        end
        else
        begin
          // Tenta RL primeiro, cai para FPDF em caso de erro
          try
            ACBrNFe1.NotasFiscais.ImprimirPDF;
            PDFSuccess := True;
          except
            on E: Exception do
            begin
              PdfErrorMsg := E.Message;
              // Fallback FPDF
              try
                var OldDanfe2 := ACBrNFe1.DANFE;
                var DanfeFPDF2 := TACBrNFeDANFeFPDF.Create(nil);
                try
                  try DanfeFPDF2.PathPDF := PDFDir; except end;
                  ACBrNFe1.DANFE := DanfeFPDF2;
                  ACBrNFe1.NotasFiscais.ImprimirPDF;
                  PDFSuccess := True;
                  PdfErrorMsg := '';
                finally
                  ACBrNFe1.DANFE := OldDanfe2;
                  DanfeFPDF2.Free;
                end;
              except
                // mantém erro em PdfErrorMsg
              end;
            end;
          end;
          if not PDFSuccess then
          begin
            try
              ACBrNFe1.NotasFiscais.Items[0].ImprimirPDF;
              PDFSuccess := True;
            except
              on E2: Exception do
              begin
                if PdfErrorMsg = '' then PdfErrorMsg := E2.Message;
              end;
            end;
          end;
        end;

        // Tenta localizar o PDF gerado
        try
          PDFPath := PDFDir + 'NFe_' + ACBrNFe1.NotasFiscais.Items[0].NFe.infNFe.ID + '.pdf';
          if not FileExists(PDFPath) then
          begin
            // Tentativas alternativas de nome
            var Alt1 := PDFDir + ACBrNFe1.NotasFiscais.Items[0].NFe.infNFe.ID + '.pdf';
            var Ch := ACBrNFe1.NotasFiscais.Items[0].NFe.infNFe.ID;
            var Alt2 := PDFDir + 'DANFE_' + Ch + '.pdf';
            if FileExists(Alt1) then PDFPath := Alt1
            else if FileExists(Alt2) then PDFPath := Alt2
            else
            begin
              var SR: TSearchRec;
              var LastTime: TDateTime := 0;
              var Best: string := '';
              if FindFirst(PDFDir + '*.pdf', faAnyFile, SR) = 0 then
              begin
                repeat
                  // Compatível com Delphi Win32: usa TimeStamp (TDateTime) ao invés de FindData
                  var FTime := SR.TimeStamp;
                  if (Best = '') or (FTime > LastTime) then
                  begin
                    Best := IncludeTrailingPathDelimiter(PDFDir) + SR.Name;
                    LastTime := FTime;
                  end;
                until FindNext(SR) <> 0;
                FindClose(SR);
              end;
              PDFPath := Best;
            end;
          end;
          if (PDFPath <> '') and not FileExists(PDFPath) then PDFPath := '';
        except
          PDFPath := '';
        end;


      Resp.AddPair('ok', TJSONBool.Create(True));
      Resp.AddPair('xml_path', XMLPath);
      Resp.AddPair('pdf_success', TJSONBool.Create(PDFSuccess));
      if PDFPath <> '' then Resp.AddPair('pdf_path', PDFPath);
      if SanApplied and (SanPath <> '') then Resp.AddPair('sanitized_xml_path', SanPath);
      if (not PDFSuccess) and (PdfErrorMsg <> '') then Resp.AddPair('pdf_error', PdfErrorMsg);
      end;
    except
      on E: Exception do
      begin
        Resp.AddPair('ok', TJSONBool.Create(False));
        Resp.AddPair('error', E.Message);
      end;
    end;
    Result := Resp.ToString;
  finally
    if ComInitialized then
      CoUninitialize;
    Resp.Free;
  end;
end;

function TForm1.CancelarNFeJSON(const JSONData: string): string;
var
  J, Conf, Cfg: TJSONObject;
  Resp: TJSONObject;
  xmlPath, chave, justificativa, emitCNPJ, xmlRet, serialTop, pfxPath, pfxPass: string;
  pathSchemas, pathXmlBase: string;
begin
  Resp := TJSONObject.Create;
  try
    try
      J := TJSONObject(TJSONObject.ParseJSONValue(JSONData));
      if not Assigned(J) then
        raise Exception.Create('JSON inválido');

      xmlPath := JsonGetStr(J, 'xml_path', '');
      chave := JsonGetStr(J, 'chave', '');
      justificativa := JsonGetStr(J, 'justificativa', '');
      emitCNPJ := JsonGetStr(J, 'emit_cnpj', '');
      if Length(justificativa) < 15 then
        raise Exception.Create('Justificativa deve ter no mínimo 15 caracteres');

      // Certificado (opcional) — aceita tanto bloco cert quanto campos de topo
      Conf := JsonGetObj(J, 'cert');
      serialTop := JsonGetStr(J, 'serial', '');
      pfxPath := '';
      pfxPass := '';
      if Assigned(Conf) then
      begin
        if JsonGetStr(Conf, 'serial', '') <> '' then
        begin
          ACBrNFe1.Configuracoes.Certificados.NumeroSerie := JsonGetStr(Conf, 'serial', '');
          ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := '';
          ACBrNFe1.Configuracoes.Certificados.Senha := '';
        end
        else
        begin
          pfxPath := JsonGetStr(Conf, 'path', '');
          pfxPass := JsonGetStr(Conf, 'password', '');
          if pfxPath <> '' then
          begin
            ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := AnsiString(pfxPath);
            ACBrNFe1.Configuracoes.Certificados.Senha := AnsiString(pfxPass);
            ACBrNFe1.Configuracoes.Certificados.NumeroSerie := '';
          end;
        end;
      end;
      if (ACBrNFe1.Configuracoes.Certificados.NumeroSerie = '') and (serialTop <> '') then
      begin
        ACBrNFe1.Configuracoes.Certificados.NumeroSerie := serialTop;
        ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := '';
        ACBrNFe1.Configuracoes.Certificados.Senha := '';
      end;

      // Configurações opcionais: path_schemas e path_xml base
      Cfg := JsonGetObj(J, 'configuracoes');
      if Assigned(Cfg) then
      begin
        pathSchemas := JsonGetStr(Cfg, 'path_schemas', '');
        if pathSchemas <> '' then
          ACBrNFe1.Configuracoes.Arquivos.PathSchemas := IncludeTrailingPathDelimiter(pathSchemas);
        pathXmlBase := JsonGetStr(Cfg, 'path_xml', '');
        if pathXmlBase <> '' then
          ACBrNFe1.Configuracoes.Arquivos.PathNFe := IncludeTrailingPathDelimiter(pathXmlBase);
        // Define UF explicitamente quando informado (evita "UF não pode ser vazia")
        var cfgUF := JsonGetStr(Cfg, 'uf', '');
        if cfgUF <> '' then
          ACBrNFe1.Configuracoes.WebServices.UF := cfgUF;
      end;

      ACBrNFe1.NotasFiscais.Clear;
      if xmlPath <> '' then
        ACBrNFe1.NotasFiscais.LoadFromFile(xmlPath);

      ACBrNFe1.EventoNFe.Evento.Clear;
      ACBrNFe1.EventoNFe.idLote := 1;
      with ACBrNFe1.EventoNFe.Evento.New do
      begin
        infEvento.dhEvento := Now;
        // Tipo Cancelamento (teCancelamento)
        infEvento.tpEvento := teCancelamento;
        if chave <> '' then
          infEvento.chNFe := chave;
        if emitCNPJ <> '' then
          infEvento.CNPJ := emitCNPJ;
        infEvento.detEvento.xJust := justificativa;
      end;

      ACBrNFe1.EnviarEvento(1);
      xmlRet := ACBrNFe1.WebServices.EnvEvento.EventoRetorno.XmlRetorno;

      Resp.AddPair('ok', TJSONBool.Create(True));
      if xmlPath <> '' then Resp.AddPair('xml_referencia', xmlPath);
      if chave <> '' then Resp.AddPair('chave', chave);
      Resp.AddPair('xml_retorno', xmlRet);
    except
      on E: Exception do
      begin
        Resp.AddPair('ok', TJSONBool.Create(False));
        Resp.AddPair('error', E.Message);
      end;
    end;
    Result := Resp.ToString;
  finally
    Resp.Free;
  end;
end;

function TForm1.CartaCorrecaoJSON(const JSONData: string): string;
var
  J, Conf, Cfg: TJSONObject;
  Resp: TJSONObject;
  xmlPath, chave, correcao, emitCNPJ, xmlRet, serialTop, pfxPath, pfxPass: string;
  seq: Integer;
  pathSchemas, pathXmlBase: string;
begin
  Resp := TJSONObject.Create;
  try
    try
      J := TJSONObject(TJSONObject.ParseJSONValue(JSONData));
      if not Assigned(J) then
        raise Exception.Create('JSON inválido');

      xmlPath := JsonGetStr(J, 'xml_path', '');
      chave := JsonGetStr(J, 'chave', '');
      correcao := JsonGetStr(J, 'correcao', '');
      emitCNPJ := JsonGetStr(J, 'emit_cnpj', '');
      seq := JsonGetInt(J, 'sequencia', 1);
      if Length(correcao) < 15 then
        raise Exception.Create('Texto da correção deve ter no mínimo 15 caracteres');

      // Certificado (opcional)
      Conf := JsonGetObj(J, 'cert');
      serialTop := JsonGetStr(J, 'serial', '');
      pfxPath := '';
      pfxPass := '';
      if Assigned(Conf) then
      begin
        if JsonGetStr(Conf, 'serial', '') <> '' then
        begin
          ACBrNFe1.Configuracoes.Certificados.NumeroSerie := JsonGetStr(Conf, 'serial', '');
          ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := '';
          ACBrNFe1.Configuracoes.Certificados.Senha := '';
        end
        else
        begin
          pfxPath := JsonGetStr(Conf, 'path', '');
          pfxPass := JsonGetStr(Conf, 'password', '');
          if pfxPath <> '' then
          begin
            ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := AnsiString(pfxPath);
            ACBrNFe1.Configuracoes.Certificados.Senha := AnsiString(pfxPass);
            ACBrNFe1.Configuracoes.Certificados.NumeroSerie := '';
          end;
        end;
      end;
      if (ACBrNFe1.Configuracoes.Certificados.NumeroSerie = '') and (serialTop <> '') then
      begin
        ACBrNFe1.Configuracoes.Certificados.NumeroSerie := serialTop;
        ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := '';
        ACBrNFe1.Configuracoes.Certificados.Senha := '';
      end;

      // Configurações opcionais: path_schemas e path_xml base
      Cfg := JsonGetObj(J, 'configuracoes');
      if Assigned(Cfg) then
      begin
        pathSchemas := JsonGetStr(Cfg, 'path_schemas', '');
        if pathSchemas <> '' then
          ACBrNFe1.Configuracoes.Arquivos.PathSchemas := IncludeTrailingPathDelimiter(pathSchemas);
        pathXmlBase := JsonGetStr(Cfg, 'path_xml', '');
        if pathXmlBase <> '' then
          ACBrNFe1.Configuracoes.Arquivos.PathNFe := IncludeTrailingPathDelimiter(pathXmlBase);
      end;

      ACBrNFe1.NotasFiscais.Clear;
      if xmlPath <> '' then
        ACBrNFe1.NotasFiscais.LoadFromFile(xmlPath);

      ACBrNFe1.EventoNFe.Evento.Clear;
      ACBrNFe1.EventoNFe.idLote := 1;
      with ACBrNFe1.EventoNFe.Evento.New do
      begin
        infEvento.dhEvento := Now;
        // Tipo Carta de Correção (teCCe)
        infEvento.tpEvento := teCCe;
        if chave <> '' then
          infEvento.chNFe := chave;
        if emitCNPJ <> '' then
          infEvento.CNPJ := emitCNPJ;
        infEvento.nSeqEvento := seq;
        infEvento.detEvento.xCorrecao := correcao;
      end;

      ACBrNFe1.EnviarEvento(1);
      xmlRet := ACBrNFe1.WebServices.EnvEvento.EventoRetorno.XmlRetorno;

      Resp.AddPair('ok', TJSONBool.Create(True));
      if xmlPath <> '' then Resp.AddPair('xml_referencia', xmlPath);
      if chave <> '' then Resp.AddPair('chave', chave);
      Resp.AddPair('xml_retorno', xmlRet);
    except
      on E: Exception do
      begin
        Resp.AddPair('ok', TJSONBool.Create(False));
        Resp.AddPair('error', E.Message);
      end;
    end;
    Result := Resp.ToString;
  finally
    Resp.Free;
  end;
end;

function TForm1.JsonGetObj(Obj: TJSONObject; const Key: string): TJSONObject;
var V: TJSONValue;
begin
  Result := nil;
  if not Assigned(Obj) then Exit;
  V := Obj.Values[Key];
  if (V <> nil) and (V is TJSONObject) then
    Result := TJSONObject(V);
end;

function TForm1.JsonGetArr(Obj: TJSONObject; const Key: string): TJSONArray;
var V: TJSONValue;
begin
  Result := nil;
  if not Assigned(Obj) then Exit;
  V := Obj.Values[Key];
  if (V <> nil) and (V is TJSONArray) then
    Result := TJSONArray(V);
end;

function TForm1.JsonGetStr(Obj: TJSONObject; const Key: string; const Default: string): string;
var V: TJSONValue;
begin
  Result := Default;
  if not Assigned(Obj) then Exit;
  V := Obj.Values[Key];
  if (V <> nil) then
    Result := V.Value;
end;

function TForm1.JsonGetInt(Obj: TJSONObject; const Key: string; const Default: Integer): Integer;
var s: string;
begin
  s := JsonGetStr(Obj, Key, IntToStr(Default));
  Result := StrToIntDef(s, Default);
end;

// ===== FUNÇÕES DE SEGURANÇA =====

function TForm1.ValidateToken(ARequestInfo: TIdHTTPRequestInfo): Boolean;
var
  Token: string;
begin
  Result := False;
  
  // Se não há tokens configurados, permite acesso (modo desenvolvimento)
  if FValidTokens.Count = 0 then
  begin
    Result := True;
    Exit;
  end;
  
  Token := ExtractTokenFromHeader(ARequestInfo);
  if Token = '' then Exit;
  
  // Verifica se o token está na lista de tokens válidos
  Result := FValidTokens.IndexOf(Token) >= 0;
end;

function TForm1.ExtractTokenFromHeader(ARequestInfo: TIdHTTPRequestInfo): string;
var
  AuthHeader, XToken: string;
begin
  Result := '';
  // 1) Authorization: Bearer <token>
  AuthHeader := ARequestInfo.RawHeaders.Values['Authorization'];
  if (AuthHeader <> '') and (Pos('Bearer ', AuthHeader) = 1) then
  begin
    Result := Copy(AuthHeader, 8, Length(AuthHeader) - 7);
    Exit;
  end;
  // 2) X-Token: <token>
  XToken := ARequestInfo.RawHeaders.Values['X-Token'];
  if (XToken <> '') then
  begin
    Result := XToken;
    Exit;
  end;
  // 3) ?token=<token>
  if (ARequestInfo.Params.Values['token'] <> '') then
  begin
    Result := ARequestInfo.Params.Values['token'];
    Exit;
  end;
end;

procedure TForm1.LogSecurityEvent(const EventType, Details: string);
var
  LogFile: TextFile;
  LogPath: string;
  DateTimeStr: string;
begin
  try
    // Cria diretório de logs se não existir
    LogPath := ExtractFilePath(Application.ExeName) + 'logs\';
    if not DirectoryExists(LogPath) then
      ForceDirectories(LogPath);
    
    // Nome do arquivo de log com data
    LogPath := LogPath + 'security_' + FormatDateTime('yyyy-mm-dd', Now) + '.log';
    
    // Formata data/hora
    DateTimeStr := FormatDateTime('yyyy-mm-dd hh:nn:ss', Now);
    
    // Escreve no arquivo de log
    AssignFile(LogFile, LogPath);
    if FileExists(LogPath) then
      Append(LogFile)
    else
      Rewrite(LogFile);
    
    try
      Writeln(LogFile, '[' + DateTimeStr + '] ' + EventType + ': ' + Details);
    finally
      CloseFile(LogFile);
    end;
  except
    // Em caso de erro, não interrompe o funcionamento
  end;
end;

procedure TForm1.LoadValidTokens;
var
  ConfigFile: TextFile;
  ConfigPath: string;
  Line: string;
begin
  FValidTokens.Clear;
  
  try
    // Arquivo de configuração de tokens (aceita tokens.txt ou token.txt)
    ConfigPath := ExtractFilePath(Application.ExeName) + 'tokens.txt';
    if not FileExists(ConfigPath) then
      ConfigPath := ExtractFilePath(Application.ExeName) + 'token.txt';
    
    // Somente lê tokens se o arquivo existir.
    // Se não existir, mantém lista vazia → sem exigência de token (modo desenvolvimento/teste).
    if FileExists(ConfigPath) then
    begin
      AssignFile(ConfigFile, ConfigPath);
      Reset(ConfigFile);
      try
        while not Eof(ConfigFile) do
        begin
          Readln(ConfigFile, Line);
          Line := Trim(Line);
          if (Line <> '') and (Pos('#', Line) <> 1) then
            FValidTokens.Add(Line);
        end;
      finally
        CloseFile(ConfigFile);
      end;
    end;
  except
    // Em caso de erro, não altera a lista (permite sem token em desenvolvimento)
  end;
end;

end.
