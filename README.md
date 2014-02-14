# Cobre Grátis WooCommerce #
**Contributors:** cobregratis, claudiosanches  
**Tags:** checkout, billet, boleto, cobregratis  
**Requires at least:** 3.5  
**Tested up to:** 3.8.1  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Start getting money by bank billet in your checking account using Cobre Grátis

## Description ##

### Description (en): ###

Start getting money by bank billet in your checking account using **Cobre Grátis**.

### Descrição (pt_BR): ###

Comece a receber dinheiro por boleto bancário direto na sua conta corrente usando o **Cobre Grátis**. Sem limites e sem taxa por boleto.

### Como Funciona o Boleto Bancário ###

[youtube http://www.youtube.com/watch?v=XhCMJ6CpD4M]

O Boleto Bancário é uma forma de pagamento exclusiva do Brasil. Qualquer pessoa física ou jurídica pode cobrar através de boletos bancários. Basta ter uma conta bancária e contratar uma carteira de cobrança junto ao banco. Aprenda mais como funciona, o que é Cedente, Sacado e o que você precisa para começar a cobrar seus clientes por Boleto Bancário.

### Integração ###

A integração é completa para vender por boleto e ainda conta com sistema de retorno que avisa quando o boleto é pago, atualizando o status do seu pedido para *processando*.

### Instalação ###

Instalar é bem simples, basta seguir o nosso [guia de instalação](http://wordpress.org/extend/plugins/cobregratis-woocommerce/installation/).

### Compatibilidade ###

Compatível com as versões 2.0.x e 2.1.x do WooCommerce.

### Dúvidas? ###

Você pode esclarecer suas dúvidas usando:

* A nossa sessão de [FAQ](http://wordpress.org/extend/plugins/cobregratis-woocommerce/faq/).
* Criando um tópico no [fórum do GitHub](https://github.com/BielSystems/cobregratis-woocommerce/issues).
* Criando um tópico no [fórum de ajuda do WordPress](http://wordpress.org/support/plugin/cobregratis-woocommerce) (apenas em inglês).

### Coloborar ###

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/BielSystems/cobregratis-woocommerce).

## Installation ##

### Instalação do plugin ###

* Faça upload dos arquivos do plugin para a sua pasta de plugins ou faça a instalação usando o instalador do WordPress em `Plugins > Adicionar Novo`;
* Ative o plugin.

### Configuração do Cobre Grátis ###

1. Crie uma conta Premium ou Max no [Cobre Grátis](http://cobregratis.com.br/planos/);
2. Com a conta é possível gerar um Token em [Cobre Grátis - Minhas informações](https://app.cobregratis.com.br/myinfo);
3. E finalmente crie um serviço em [Cobre Grátis - Serviços](https://app.cobregratis.com.br/services/new);
4. No serviço criado ative a opção **Usar integração de notificação de pagamento**;
5. Crie um **Código de Segurança** para a integração do serviço;
6. E configure a **URL para webhook de pagamento de boletos** como por exemplo `http://seusite.com.br/?wc-api=WC_Cobregratis_Gateway`;
7. Pronto, conta configurada.

### Configuração do plugin ###

1. Vá até `WooCommerce > Configurações > Finalizar compra > Cobre Grátis`;
2. Habilite o **Cobre Grátis** e preencha como preferir as opções de *Título* de *Descrição*;
3. Digite o token gerado na sua conta do **Cobre Grátis**;
4. Por final preencha também a opção *Código de segurança do webhook*;
5. Salve as configurações;
6. Vá até `WooCommerce > Configurações > Produtos > Inventário`;
7. Deixe em branco a opção **Manter estoque (minutos)** (isso evita problemas com contas canceladas antes do cliente pagar o boleto).
8. Salve novamente as configurações;
9. Tudo pronto para receber pagamentos via boleto bancário usando o **Cobre Grátis**.

## Frequently Asked Questions ##

### O que eu preciso para utilizar este plugin? ###

* Ter instalado o WooCommerce 2.0.x ou superior.
* Uma conta Premium ou Max no [Cobre Grátis](http://cobregratis.com.br/planos/);
* Gerar um token de segurança na sua conta em [Cobre Grátis - Minhas informações](https://app.cobregratis.com.br/myinfo);
* Criar um serviço em [Cobre Grátis - Serviços](https://app.cobregratis.com.br/services/new);
* Ativar a integração do serviço com a opção **Usar integração de notificação de pagamento**;
* Gerar um **Código de Segurança** para a integração do serviço;
* Configurar uma **URL para webhook de pagamento de boletos**.

## For Developers ##

É possível usar qualquer um dos exemplos abaixo dentro do `functions.php` do seu tema ou criando um plugin (veja como em [WordPress - Writing a Plugin](http://codex.wordpress.org/Writing_a_Plugin)).

## Adicionar um ícone no método de pagamento ##

	/**
	 * Adicionar um ícone para Cobre Grátis.
	 *
	 * @param  string $url String vazia.
	 *
	 * @return string      Link para o seu ícone.
	 */
	function custom_woocommerce_cobregratis_icon( $url ) {
		return 'link do ícone';
	}

	add_filter( 'woocommerce_cobregratis_icon', 'custom_woocommerce_cobregratis_icon' );

## Alterar os parametros postados para o Cobre Grátis ##

	/**
	 * Customizar os dados postados para o Cobre Grátis.
	 *
	 * @param  array    $data  Dados gerados pelo plugin.
	 * @param  WC_Order $order Objeto que contém todas as informações do pedido.
	 *
	 * @return array
	 */
	function custom_woocommerce_cobregratis_billet_data( $data, $order ) {
		// aqui você pode trabalhar e alterar o array $data com o que desejar.
		// Api do Cobre Grátis: https://github.com/BielSystems/cobregratis-api

		return $data;
	}

	add_filter( 'woocommerce_cobregratis_billet_data', 'custom_woocommerce_cobregratis_billet_data', 10, 2 );

## Alterar as instruções do boleto na página de "obrigado" (thankyou page) ##

	/**
	 * Customizar as instruções sobre o boleto na página "obrigado".
	 *
	 * @param  string $message  Mensagem padrão do plugin.
	 * @param  int    $order_id ID do pedido.
	 *
	 * @return string           Novas instruções.
	 */
	function custom_woocommerce_cobregratis_thankyou_page_instructions( $message, $order_id ) {
		return 'Novas instruções';
	}

	add_filter( 'woocommerce_cobregratis_thankyou_page_instructions', 'custom_woocommerce_cobregratis_thankyou_page_instructions', 10, 2 );

## Alterar as instruções do boleto no e-mail ##

	/**
	 * Customizar as instruções sobre o boleto no e-mail.
	 *
	 * @param  string   $message Mensagem padrão do plugin.
	 * @param  WC_Order $order   Objeto que contém todas as informações do pedido.
	 *
	 * @return string            Novas instruções.
	 */
	function custom_woocommerce_cobregratis_email_instructions( $message, $order ) {
		return 'Novas instruções';
	}

	add_filter( 'woocommerce_cobregratis_email_instructions', 'custom_woocommerce_cobregratis_email_instructions', 10, 2 );

## Alterar as instruções do boleto para pedidos que estão aguardando pagamento ##

	/**
	 * Customizar as instruções do boleto para pedidos que estão aguardando pagamento.
	 *
	 * @param  string   $message Mensagem padrão do plugin.
	 * @param  WC_Order $order   Objeto que contém todas as informações do pedido.
	 *
	 * @return string            Novas instruções.
	 */
	function custom_woocommerce_cobregratis_pending_payment_instructions( $message, $order ) {
		return 'Novas instruções';
	}

	add_filter( 'woocommerce_cobregratis_pending_payment_instructions', 'custom_woocommerce_cobregratis_pending_payment_instructions', 10, 2 );

## Screenshots ##

### 1. Plugin Settings. ###
![1. Plugin Settings.](http://s.wordpress.org/extend/plugins/cobre-grátis-woocommerce/screenshot-1.png)


## Changelog ##

### 1.0.0 ###

* Initial release.

## Upgrade Notice ##

### 1.0.0 ###

* Initial release.

## License ##

Cobre Grátis WooCommerce is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

Cobre Grátis WooCommerce is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Cobre Grátis WooCommerce. If not, see <http://www.gnu.org/licenses/>.
