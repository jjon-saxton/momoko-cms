// ----------------------------------------------------------------------------
// markItUp!
// ----------------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
// BBCode tags example
// http://en.wikipedia.org/wiki/Bbcode
// ----------------------------------------------------------------------------
// Feel free to add more tags
// ----------------------------------------------------------------------------
BBCodeFull = {
	previewParserPath:	'', // path to your BBCode parser
	markupSet: [
		{name:'Bold', key:'B', openWith:'[b]', closeWith:'[/b]'},
		{name:'Italic', key:'I', openWith:'[i]', closeWith:'[/i]'},
		{name:'Underline', key:'U', openWith:'[u]', closeWith:'[/u]'},
		{name:'Strike-through', key:'S', openWith:'[s]', closeWith:'[/s]'},
		{separator:'---------------' },
		{name:'Picture', key:'P', replaceWith:'[img][![Url]!][/img]'},
		{name:'Link', key:'L', openWith:'[url=[![Url]!]]', closeWith:'[/url]', placeHolder:'Your text to link here...'},
		{separator:'---------------' },	
		{	name:'Colors', 
			className:'colors', 
			openWith:'[color=[![Color]!]]', 
			closeWith:'[/color]', 
				dropMenu: [
					{name:'Yellow',	openWith:'[color=yellow]', 	closeWith:'[/color]', className:"col1-1" },
					{name:'Orange',	openWith:'[color=orange]', 	closeWith:'[/color]', className:"col1-2" },
					{name:'Red', 	openWith:'[color=red]', 	closeWith:'[/color]', className:"col1-3" },
					
					{name:'Blue', 	openWith:'[color=blue]', 	closeWith:'[/color]', className:"col2-1" },
					{name:'Purple', openWith:'[color=purple]', 	closeWith:'[/color]', className:"col2-2" },
					{name:'Green', 	openWith:'[color=green]', 	closeWith:'[/color]', className:"col2-3" },
					
					{name:'White', 	openWith:'[color=white]', 	closeWith:'[/color]', className:"col3-1" },
					{name:'Gray', 	openWith:'[color=gray]', 	closeWith:'[/color]', className:"col3-2" },
					{name:'Black',	openWith:'[color=black]', 	closeWith:'[/color]', className:"col3-3" }
				]
		},
		{name:'Size', className:'fonts', key:'S', openWith:'[size=[![Text size]!]]', closeWith:'[/size]',
		dropMenu :[
		  {name:'XL', openWith:'[size=4]', closeWith:'[/size]' },
			{name:'Large', openWith:'[size=2]', closeWith:'[/size]' },
			{name:'Normal', openWith:'[size=1]', closeWith:'[/size]' },
			{name:'Small', openWith:'[size=0.5]', closeWith:'[/size]' },
			{name:'XS', openWith:'[size=0.3]', closeWith:'[/size]' }
		]},
		{separator:'---------------' },
		{name:'Bulleted list', className: 'blist', openWith:'[list]\n', closeWith:'\n[/list]'},
		{name:'Numeric list', className: 'nlist', openWith:'[list=[![Starting number]!]]\n', closeWith:'\n[/list]'}, 
		{name:'List item', className: 'listitem', openWith:'[*] '},
		{separator:'---------------' },
		{name:'Quotes', className: 'quote', openWith:'[quote]', closeWith:'[/quote]'},
		{name:'Code', className: 'code', openWith:'[code]', closeWith:'[/code]'}, 
		{separator:'---------------' },
		{name:'Clean', className:"clean", replaceWith:function(markitup) { return markitup.selection.replace(/\[(.*?)\]/g, "") } },
		{name:'Preview', className:"preview", call:'preview' }
	]
}

BBCodeLight = {
	previewParserPath:	'', // path to your BBCode parser
	markupSet: [
		{name:'Bold', key:'B', openWith:'[b]', closeWith:'[/b]'},
		{name:'Italic', key:'I', openWith:'[i]', closeWith:'[/i]'},
		{name:'Underline', key:'U', openWith:'[u]', closeWith:'[/u]'},
		{name:'Strike-through', key:'S', openWith:'[s]', closeWith:'[/s]'},
		{separator:'---------------' },
		{name:'Picture', key:'P', replaceWith:'[img][![Url]!][/img]'},
		{name:'Link', key:'L', openWith:'[url=[![Url]!]]', closeWith:'[/url]', placeHolder:'Your text to link here...'},
		{separator:'---------------' },
		{name:'Bulleted list', className: 'blist', openWith:'[list]\n', closeWith:'\n[/list]'},
		{name:'Numeric list', className: 'nlist', openWith:'[list=[![Starting number]!]]\n', closeWith:'\n[/list]'}, 
		{name:'List item', className: 'listitem', openWith:'[*] '},
		{separator:'---------------' },
		{name:'Clean', className:"clean", replaceWith:function(markitup) { return markitup.selection.replace(/\[(.*?)\]/g, "") } },
		{name:'Preview', className:"preview", call:'preview' }
	]
}