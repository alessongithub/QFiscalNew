# 🚀 QFiscal ERP - Landing Page

Uma landing page moderna e tecnológica para o ERP QFiscal, com design responsivo e funcionalidades interativas.

## ✨ Características

- **Design Moderno**: Interface escura com tema verde e preto baseado na logo
- **Responsivo**: Funciona perfeitamente em todos os dispositivos
- **Interativo**: Modal para ampliar imagens das telas do sistema
- **Animações**: Efeitos visuais suaves e partículas em background
- **Formulário de Contato**: Sistema completo para capturar leads
- **WhatsApp Integration**: Botão flutuante para contato direto
- **SEO Otimizado**: Meta tags e estrutura semântica

## 🎨 Seções da Landing

1. **Hero Section**: Apresentação principal com badge "EM BREVE"
2. **Recursos**: 8 principais funcionalidades do ERP
3. **Telas**: Screenshots do sistema com modal de ampliação
4. **Preços**: Preview dos planos (a partir de R$ 39,00)
5. **Contato**: Formulário para captura de leads
6. **Footer**: Links e informações da empresa

## 🛠️ Tecnologias Utilizadas

- **HTML5**: Estrutura semântica
- **CSS3**: Estilos modernos com variáveis CSS e Flexbox/Grid
- **JavaScript**: Interatividade e funcionalidades
- **Font Awesome**: Ícones vetoriais
- **Google Fonts**: Tipografia Inter

## 📱 Funcionalidades JavaScript

- Modal para ampliar imagens das telas
- Smooth scroll para navegação interna
- Animações de entrada com Intersection Observer
- Formulário de contato funcional
- Sistema de notificações
- Efeitos de parallax e partículas
- Menu mobile responsivo
- Contadores animados

## 🚀 Como Usar

1. **Abra o arquivo**: `index.html` em qualquer navegador moderno
2. **Navegue**: Use o menu superior para acessar as seções
3. **Visualize as telas**: Clique nas imagens para ampliar
4. **Preencha o formulário**: Deixe seus dados para ser avisado
5. **Entre em contato**: Use o botão WhatsApp flutuante

## 📁 Estrutura de Arquivos

```
site/
├── index.html          # Página principal
├── styles.css          # Estilos CSS
├── script.js           # Funcionalidades JavaScript
├── README.md           # Este arquivo
└── ../logo/            # Imagens da logo e telas
    ├── logo.png        # Logo principal
    └── telas/          # Screenshots do sistema
```

## 🎯 Personalização

### Cores
As cores principais estão definidas em variáveis CSS no arquivo `styles.css`:
```css
:root {
    --primary-color: #00d4aa;    /* Verde principal */
    --primary-dark: #00b894;     /* Verde escuro */
    --secondary-color: #1a1a1a;  /* Preto */
    --accent-color: #00f5d4;     /* Verde claro */
}
```

### Conteúdo
- **Texto**: Edite o arquivo `index.html` para alterar textos
- **Imagens**: Substitua as imagens na pasta `../logo/`
- **Contatos**: Atualize telefones e emails no HTML
- **WhatsApp**: Altere o número no link do botão flutuante

### Funcionalidades
- **Formulário**: Configure o backend no arquivo `script.js`
- **Animações**: Ajuste velocidades e efeitos no CSS
- **Partículas**: Modifique quantidade e comportamento no JavaScript

## 📧 Integração com Backend

Para integrar o formulário de contato com seu backend:

1. **Edite o arquivo `script.js`**
2. **Localize a função de envio do formulário**
3. **Substitua a simulação por uma requisição real**:

```javascript
// Exemplo com fetch API
fetch('/api/contact', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(formData)
})
.then(response => response.json())
.then(data => {
    showNotification('Mensagem enviada com sucesso!', 'success');
    contactForm.reset();
})
.catch(error => {
    showNotification('Erro ao enviar mensagem. Tente novamente.', 'error');
});
```

## 🌐 Deploy

### Opções de Hospedagem

1. **GitHub Pages**: Gratuito para projetos públicos
2. **Netlify**: Deploy automático com drag & drop
3. **Vercel**: Deploy rápido e gratuito
4. **Servidor Web**: Apache, Nginx, etc.

### Configuração

1. **Faça upload** dos arquivos para seu servidor
2. **Configure** o domínio se necessário
3. **Teste** todas as funcionalidades
4. **Monitore** o formulário de contato

## 📱 Responsividade

A landing page é totalmente responsiva e funciona em:
- **Desktop**: 1200px+
- **Tablet**: 768px - 1199px
- **Mobile**: 320px - 767px

## 🔧 Manutenção

### Atualizações Regulares
- **Conteúdo**: Mantenha informações atualizadas
- **Imagens**: Substitua screenshots por versões mais recentes
- **Links**: Verifique se todos os links estão funcionando
- **Performance**: Otimize imagens e código regularmente

### Monitoramento
- **Analytics**: Implemente Google Analytics ou similar
- **Formulários**: Monitore conversões e leads
- **Performance**: Use PageSpeed Insights para otimizações

## 📞 Suporte

Para dúvidas ou suporte técnico:
- **Email**: contato@qfiscal.com.br
- **WhatsApp**: 947146126

## 📄 Licença

Este projeto é propriedade da QFiscal ERP. Todos os direitos reservados.

---

**Desenvolvido com ❤️ para revolucionar a gestão empresarial no Brasil!**
