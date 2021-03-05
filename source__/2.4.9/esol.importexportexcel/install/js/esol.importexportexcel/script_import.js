var kdaIEModuleName = 'esol.importexportexcel';
var kdaIEModuleFilePrefix = 'esol_import_excel';
var kdaIEModuleAddPath = 'import/';
var EList = {
	cfields: {},
	
	Init: function()
	{
		var obj = this;
		this.InitLines();
		
		$('.kda-ie-tbl input[type=checkbox][name^="SETTINGS[CHECK_ALL]"]').bind('change', function(){
			var inputs = $(this).closest('tbody').find('input[type=checkbox]').not(this);
			if(this.checked)
			{
				inputs.prop('checked', true);
			}
			else
			{
				inputs.prop('checked', false);
			}
		});
		
		/*Bug fix with excess jquery*/
		var anySelect = $('select:eq(0)');
		if(typeof anySelect.chosen != 'function')
		{
			var jQuerySrc = $('script[src^="/bitrix/js/main/jquery/"]').attr('src');
			if(jQuerySrc)
			{
				$.getScript(jQuerySrc, function(){
					$.getScript('/bitrix/js/'+kdaIEModuleName+'/chosen/chosen.jquery.min.js');
				});
			}
		}
		/*/Bug fix with excess jquery*/
		//this.SetFieldValues();
		
		/*$('.kda-ie-tbl select[name^="SETTINGS[FIELDS_LIST]"]').bind('change', function(){
			EList.OnChangeFieldHandler(this);
		}).trigger('change');*/
		
		$('.kda-ie-tbl input[type=checkbox][name^="SETTINGS[LIST_ACTIVE]"]').bind('click', function(e){
			if(e.shiftKey && (typeof obj.lastSheetChb == 'object') && obj.lastSheetChb.checked==this.checked)
			{
				var sheetKey1 = $(obj.lastSheetChb).closest('.kda-ie-tbl').attr('data-list-index');
				var sheetKey2 = $(this).closest('.kda-ie-tbl').attr('data-list-index');
				var kFrom = Math.min(sheetKey1, sheetKey2);
				var kTo = Math.max(sheetKey1, sheetKey2);
				for(var i=kFrom+1; i<kTo; i++)
				{
					$('#list_active_'+i).prop('checked', this.checked);
				}
				obj.lastSheetChb = this;
				return;
			}
			obj.lastSheetChb = this;
			
			var showBtn = $(this).closest('td').find('a.showlist');
			if((this.checked && !showBtn.hasClass('open')) || (!this.checked && showBtn.hasClass('open'))) showBtn.trigger('click');
		});
		
		$('.kda-ie-tbl:not(.empty) tr.heading input[name^="SETTINGS[LIST_ACTIVE]"]:checked:eq(0)').closest('tr.heading').find('.showlist').trigger('click');
		//$('.kda-ie-tbl:not(.empty) tr.heading .showlist:eq(0)').trigger('click');
		
		
		$('.kda-ie-tbl:not(.empty) div.set').bind('scroll', function(){
			$('#kda_select_chosen').remove();
			$(this).prev('.set_scroll').scrollLeft($(this).scrollLeft());
		});
		$('.kda-ie-tbl:not(.empty) div.set_scroll').bind('scroll', function(){
			$('#kda_select_chosen').remove();
			$(this).next('.set').scrollLeft($(this).scrollLeft());
		});
		$(window).bind('resize', function(){
			EList.SetWidthList();
			$('.kda-ie-tbl tr.settings .set_scroll').removeClass('fixed');
			$('.kda-ie-tbl tr.settings .set_scroll_static').remove();
		});
		BX.addCustomEvent("onAdminMenuResize", function(json){
			$(window).trigger('resize');
		});
		$(window).trigger('resize');
		
		$(window).bind('scroll', function(){
			var windowHeight = $(window).height();
			var scrollTop = $(window).scrollTop();
			$('.kda-ie-tbl tr.settings:visible').each(function(){
				var height = $(this).height();
				if(height <= windowHeight) return;
				var top = $(this).offset().top;
				var scrollDiv = $('.set_scroll', this);
				if(scrollTop > top && scrollTop < top + height - windowHeight)
				{
					if(!scrollDiv.hasClass('fixed'))
					{
						scrollDiv.before('<div class="set_scroll_static" style="height: '+(scrollDiv.height())+'px"></div>');
						scrollDiv.addClass('fixed');
					}
				}
				else if(scrollDiv.hasClass('fixed'))
				{
					$('.set_scroll_static', this).remove();
					scrollDiv.removeClass('fixed');
				}
			});
		});
		
		var sectionSelect = $('.kda-ie-tbl table.additional select');
		if(typeof sectionSelect.chosen == 'function') sectionSelect.chosen({search_contains: true, placeholder_text: BX.message("KDA_IE_SELECT_NOT_CHOSEN")});
	},
	
	InitLines: function(list)
	{
		var obj = this;
		$('.kda-ie-tbl .list input[name^="SETTINGS[IMPORT_LINE]"]').click(function(e){
			if(typeof obj.lastChb != 'object') obj.lastChb = {};
			var arKeys = this.name.substr(0, this.name.length - 1).split('][');
			var chbKey = arKeys.pop();
			var sheetKey = arKeys.pop();
			if(e.shiftKey && obj.lastChb[sheetKey] && obj.lastChb[sheetKey].checked==this.checked)
			{
				var arKeys2 = obj.lastChb[sheetKey].name.substr(0, obj.lastChb[sheetKey].name.length - 1).split('][');
				var chbKey2 = arKeys2.pop();
				var kFrom = Math.min(chbKey, chbKey2);
				var kTo = Math.max(chbKey, chbKey2);
				for(var i=kFrom+1; i<kTo; i++)
				{
					$('.kda-ie-tbl .list input[name="SETTINGS[IMPORT_LINE]['+sheetKey+']['+i+']"]').prop('checked', this.checked);
				}
			}
			obj.lastChb[sheetKey] = this;
		});
		
		if(typeof admKDAMessages=='object' && typeof admKDAMessages.lineActions=='object')
		{
			var i = 0;
			for(var k in admKDAMessages.lineActions){i++};
			
			if(false /* || (i == 0)*/)
			{
				$('.kda-ie-tbl .list .sandwich').hide();
			}
			else
			{
				var sandwichSelector = '.kda-ie-tbl .list .sandwich';
				if(typeof list!='undefined') sandwichSelector = '.kda-ie-tbl[data-list-index='+list+'] .list .sandwich';
				
				$(sandwichSelector).unbind('click').bind('click', function(){
					if(this.getAttribute('data-type')=='titles')
					{
						var list = $(this).closest('.kda-ie-tbl').attr('data-list-index');
						var menuItems = [];
						menuItems.push({
							TEXT: BX.message("KDA_IE_INSERT_ALL_FIND_VALUES"),
							ONCLICK: 'EList.InsertAllFindValues("'+list+'")'
						});
						
						var tbl = $(this).closest('.kda-ie-tbl');
						var stInput = $('input[name="SETTINGS[LIST_SETTINGS]['+tbl.attr('data-list-index')+'][SET_TITLES]"]', tbl);
						if(stInput.length > 0 && stInput.val().length > 0)
						{
							menuItems.push({
								TEXT: BX.message("KDA_IE_CREATE_NEW_PROPERTIES"),
								ONCLICK: 'EList.CreateNewProperties("'+list+'")'
							});
							var bfInput = $('input[name="SETTINGS[LIST_SETTINGS]['+tbl.attr('data-list-index')+'][BIND_FIELDS_TO_HEADERS]"]', tbl);
							if(bfInput.length == 0 || bfInput.val()!='1')
							{
								menuItems.push({
									TEXT: BX.message("KDA_IE_BIND_FIELDS_TO_HEADERS"),
									ONCLICK: 'EList.BindFieldsToHeaders("'+list+'")'
								});
							}
							else
							{
								menuItems.push({
									TEXT: BX.message("KDA_IE_UNBIND_FIELDS_TO_HEADERS"),
									ONCLICK: 'EList.UnbindFieldsToHeaders("'+list+'")'
								});
							}
						}
						
						var hecInput = $('input[name="SETTINGS[LIST_SETTINGS]['+tbl.attr('data-list-index')+'][HIDE_EMPTY_COLUMNS]"]', tbl);
						if(hecInput.length == 0 || hecInput.val()!='1')
						{
							menuItems.push({
								TEXT: BX.message("KDA_IE_HIDE_EMPTY_COLUMNS"),
								ONCLICK: 'EList.HideEmptyColumns("'+list+'")'
							});
						}
						else
						{
							menuItems.push({
								TEXT: BX.message("KDA_IE_SHOW_EMPTY_COLUMNS"),
								ONCLICK: 'EList.ShowEmptyColumns("'+list+'")'
							});
						}
						
						if(this.OPENER) this.OPENER.SetMenu(menuItems);
						BX.adminShowMenu(this, menuItems, {active_class: "bx-adm-scale-menu-butt-active"});
					}
					else
					{
						var inputName = $(this).closest('tr').find('input[name^="SETTINGS[IMPORT_LINE]"]:eq(0)').attr('name');
						var arKeys = inputName.substr(22, inputName.length - 23).split('][');
						
						var menuItems = [];
						menuItems.push({
							TEXT: BX.message("KDA_IE_ROW_SET_TITLES"),
							ONCLICK: 'EList.SetLineAction("SET_TITLES", "'+arKeys[0]+'", "'+arKeys[1]+'")'
						});
						menuItems.push({
							TEXT: BX.message("KDA_IE_ROW_SET_HINTS"),
							ONCLICK: 'EList.SetLineAction("SET_HINTS", "'+arKeys[0]+'", "'+arKeys[1]+'")'
						});
						
						if(typeof admKDAMessages.lineActions.SECTION=='object')
						{
							var sectionItems = admKDAMessages.lineActions.SECTION;
							var menuSectionItems = [];
							for(var k in sectionItems)
							{
								menuSectionItems.push({
									TEXT: sectionItems[k].TEXT,
									ONCLICK: 'EList.SetLineAction("'+k+'", "'+arKeys[0]+'", "'+arKeys[1]+'")'
								});
							}
							if(menuSectionItems.length > 0)
							{
								menuItems.push({
									TEXT: BX.message("KDA_IE_ROW_SECTION"),
									MENU: menuSectionItems
								});
							}
						}
						if(typeof admKDAMessages.lineActions.PROPERTY=='object')
						{
							var propertyItems = admKDAMessages.lineActions.PROPERTY;
							var menuPropertyItems = [];
							for(var k in propertyItems)
							{
								menuPropertyItems.push({
									TEXT: propertyItems[k].TEXT,
									ONCLICK: 'EList.SetLineAction("'+k+'", "'+arKeys[0]+'", "'+arKeys[1]+'")'
								});
							}
							if(menuPropertyItems.length > 0)
							{
								menuItems.push({
									TEXT: BX.message("KDA_IE_ROW_PROPERTY"),
									MENU: menuPropertyItems
								});
							}
						}
						menuItems.push({
							TEXT: BX.message("KDA_IE_ROW_REMOVE_ACTION"),
							ONCLICK: 'EList.SetLineAction("REMOVE_ACTION", "'+arKeys[0]+'", "'+arKeys[1]+'")'
						});
						BX.adminShowMenu(this, menuItems, {active_class: "bx-adm-scale-menu-butt-active"});
					}
				});
			}
			
			var inputsSelector = '.kda-ie-tbl input[name^="SETTINGS[LIST_SETTINGS]"]';
			if(typeof list!='undefined') inputsSelector = '.kda-ie-tbl[data-list-index='+list+'] input[name^="SETTINGS[LIST_SETTINGS]"]';

			$(inputsSelector).each(function(){
				EList.ShowLineActions(this, true);
			});
		}
	},
	
	InsertAllFindValues: function(list)
	{
		$('.kda-ie-tbl[data-list-index='+list+'] .list tr:first a.field_insert').trigger('click');
	},
	
	SetFieldValues: function(gParent, rewriteAutoinsert)
	{
		if(!gParent) gParent = $('.kda-ie-tbl');
		$('select[name^="FIELDS_LIST["]', gParent).each(function(){
			var pSelect = this;
			var parent = $(pSelect).closest('tr');
			var arVals = [];
			var arValParents = [];
			for(var i=0; i<pSelect.options.length; i++)
			{
				arVals[pSelect.options.item(i).value] = pSelect.options.item(i).text;
				arValParents[pSelect.options.item(i).value] = pSelect.options.item(i).parentNode.getAttribute('label');
			}
			$('.kda-ie-field-select', parent).unbind('mousedown').bind('mousedown', function(event){
				var td = this;
				var timer = setTimeout(function(){
					EList.ChooseColumnForMove(td, event);
				}, 200);
				$(window).one('mouseup', function(){
					clearTimeout(timer);
				});
			}).unbind('selectstart').bind('selectstart', function(){return false;});
			EList.SetFieldAutoInsert(parent, rewriteAutoinsert);
			
			$('input[name^="SETTINGS[FIELDS_LIST]"]', parent).each(function(index){
				var input = this;
				/*var inputShow = $('input[name="'+input.name.replace('SETTINGS[FIELDS_LIST]', 'FIELDS_LIST_SHOW')+'"]', parent)[0];
				inputShow.setAttribute('placeholder', arVals['']);*/
				var inputShow = EList.GetShowInputFromValInput(input);
				inputShow.setAttribute('placeholder', arVals['']);
				
				if(!input.value || !arVals[input.value])
				{
					//input.value = '';
					//inputShow.value = '';
					EList.SetHiddenFieldVal(input, '');
					EList.SetShowFieldVal(inputShow, '');
					return;
				}
				/*inputShow.value = arVals[input.value];
				inputShow.title = arVals[input.value];*/
				EList.SetHiddenFieldVal(input, input.value);
				EList.SetShowFieldVal(inputShow, arVals[input.value], arValParents[input.value]);
			});
			
			EList.OnFieldFocus($('span.fieldval', parent));
		});
	},
	
	GetShowInputNameFromValInputName: function(name)
	{
		return name.replace(/SETTINGS\[FIELDS_LIST\]\[([\d_]*)\]\[([\d_]*)\]/, 'field-list-show-$1-$2');
	},
	
	GetShowInputFromValInput: function(input)
	{
		return $('#'+this.GetShowInputNameFromValInputName(input.name))[0];
	},
	
	GetValInputFromShowInput: function(input)
	{
		return  $(input).closest('td').find('input[name="'+input.id.replace(/field-list-show-([\d_]*)-([\d_]*)$/, 'SETTINGS[FIELDS_LIST][$1][$2]')+'"]')[0];
	},
	
	InArray: function(val, arr)
	{
		for(var i=0; i<arr.length; i++)
		{
			if(arr[i]==val) return true;
		}
		return false;
	},
	
	SetHiddenFieldVal: function(input, val)
	{
		input = $(input);
		var arKeys = input.attr('name').replace(/SETTINGS\[FIELDS_LIST\]\[([\d_]*)\]\[([\d_]*)\]/, '$1|$2').split('|');
		if(!this.cfields[arKeys[0]]) this.cfields[arKeys[0]] = {};
		
		var oldVal = input.val();
		if(oldVal!=val && oldVal.length > 0 && this.cfields[arKeys[0]][oldVal] && this.cfields[arKeys[0]][oldVal].length > 1)
		{
			var fkeys = this.cfields[arKeys[0]][oldVal];
			this.cfields[arKeys[0]][oldVal] = [];
			for(var i=0; i<fkeys.length; i++)
			{
				if(arKeys[1]==fkeys[i])
				{
					$('#field-list-show-'+arKeys[0]+'-'+fkeys[i]).removeClass('fieldval_duplicated');
					continue;
				}
				this.cfields[arKeys[0]][oldVal].push(fkeys[i]);
			}
			var fkeys = this.cfields[arKeys[0]][oldVal];
			if(fkeys.length == 1)
			{
				for(var i=0; i<fkeys.length; i++)
				{
					$('#field-list-show-'+arKeys[0]+'-'+fkeys[i]).removeClass('fieldval_duplicated');
				}
			}
		}
		
		if(val.length > 0)
		{
			if(!this.cfields[arKeys[0]][val]) this.cfields[arKeys[0]][val] = [];
			if(!this.InArray(arKeys[1], this.cfields[arKeys[0]][val])) this.cfields[arKeys[0]][val].push(arKeys[1]);
			var fkeys = this.cfields[arKeys[0]][val];
			if(fkeys.length > 1)
			{
				for(var i=0; i<fkeys.length; i++)
				{
					$('#field-list-show-'+arKeys[0]+'-'+fkeys[i]).addClass('fieldval_duplicated');
				}
			}
		}
			
		input.val(val);
	},
	
	SetShowFieldVal: function(input, val, group)
	{
		input = $(input);
		var jsInput = input[0];
		var placeholder = jsInput.getAttribute('placeholder');
		if(val.length > 0 && val!=placeholder)
		{
			jsInput.innerHTML = val;
			input.removeClass('fieldval_empty');
		}
		else
		{
			jsInput.innerHTML = placeholder;
			input.addClass('fieldval_empty');
		}
		jsInput.title = (group ? group+' - ' : '')+val;
	},
	
	SetFieldAutoInsert: function(tr, rewriteAutoinsert)
	{
		var pSelect = tr.closest('.kda-ie-tbl').find('select[name^="FIELDS_LIST["]');
		if(pSelect.length==0) return;
		pSelect = pSelect[0];
		var arVals = [], arValsLowered = [], arValsCorrected = [], arValParents = [], key = '';
		for(var i=0; i<pSelect.options.length; i++)
		{
			key = pSelect.options.item(i).value;
			arVals[key] = pSelect.options.item(i).text;
			arValsLowered[key] = $.trim(pSelect.options.item(i).text.toLowerCase()).replace(/(&nbsp;|\s)+/g, ' ');
			arValsCorrected[key] = arValsLowered[key].replace(/^[^"]*"/, '').replace(/"[^"]*$/, '').replace(/\s+\[[\d\w_]*\]$/, '');
			arValParents[key] = pSelect.options.item(i).parentNode.getAttribute('label');
			if(arValParents[key]) arValParents[key] = arValParents[key].replace(/^.*(\d+\D{5}).*$/, '$1');
		}
		
		var table = $(tr).closest('table');
		var ctr = $('.slevel[data-level-type="headers"]', table).closest('tr');
		var fselect = $('.kda-ie-field-select', tr);
		var cntCheckCells = (fselect.length > 1000 ? 5 : 10);
		var timeBegin = (new Date()).getTime();

		fselect.each(function(index){
			$('.field_insert', this).remove();
			if((new Date()).getTime() - timeBegin > 3000) return;
			var input = $('input[name^="SETTINGS[FIELDS_LIST]"]:eq(0)', this);
			//var inputShow = $('input[name^="FIELDS_LIST_SHOW"]:eq(0)', this);
			var inputShow = $('span.fieldval:eq(0)', this);
			//var firstVal = $(this).closest('table').find('tr:gt(0) > td:nth-child('+(index+2)+') .cell_inner:not(:empty):eq(0)').text().toLowerCase();

			if(!$(this).attr('data-autoinsert-init') || rewriteAutoinsert)
			{
				var ind = false;
				
				if(ctr.length > 0 && ctr[0].cells[index+1])
				{
					var inputVals = $(ctr[0].cells[index+1]).find('.cell_inner:not(:empty)');
				}
				else
				{
					var inputVals = table.find('tr:gt(0) > td:nth-child('+(index+2)+') .cell_inner:not(:empty):lt('+cntCheckCells+')');
				}

				var length = -1;
				var bFind = false;
				if(inputVals && inputVals.length > 0)
				{
					for(var j=0; j<inputVals.length; j++)
					{
						var firstValOrig = inputVals[j].innerHTML;
						var firstVal = $.trim(firstValOrig.toLowerCase()).replace(/(&nbsp;|\s)+/g, ' ');
						var firstValCode = firstVal.replace(/^.*\[(.*)\].*$/, '$1');
						for(var i in arValsLowered)
						{
							if(firstValOrig.indexOf('{'+i+'}')!=-1)
							{
								ind = i;
								bFind = true;
								break;
							}
							var lowerVal = arValsLowered[i];
							var lowerValCorrected = arValsCorrected[i];
							if(i.indexOf('ISECT')==0 && !i.match(/ISECT\d+_NAME/) && lowerVal.indexOf('seo')==-1) continue;
							if(
								(firstVal.indexOf(lowerVal)!=-1) 
								|| (lowerValCorrected.length > 0 && firstVal.indexOf(lowerValCorrected)!=-1) 
								|| (firstValCode.length > 0 && lowerVal.indexOf('['+firstValCode+']'))!=-1
								|| (i.indexOf('ISECT')==0 && firstVal.indexOf(arValParents[i])!=-1))
							{
								if(length < 0)
								{
									length = firstVal.replace(lowerVal, '').replace(lowerValCorrected, '').length;
									ind = i;
								}
								else if(firstVal.replace(lowerVal, '').replace(lowerValCorrected, '').length < length)
								{
									length = firstVal.replace(lowerVal, '').replace(lowerValCorrected, '').length;
									ind = i;
								}
							}
						}
						if(bFind) break;
					}
				}
			}
			else
			{
				var ind = $(this).attr('data-autoinsert-index');
				if(ind == 'false') ind = false;
			}
			
			if(ind)
			{
				$('a.field_settings:eq(0)', this).before('<a href="javascript:void(0)" class="field_insert" title=""></a>');
				$('a.field_insert', this).attr('title', arVals[ind]+' ('+BX.message("KDA_IE_INSERT_FIND_FIELD")+')')
					.attr('data-value', ind)
					.attr('data-show-value', arVals[ind])
					.attr('data-show-group', arValParents[ind])
					.bind('click', function(){
						//input.val(this.getAttribute('data-value'));
						//inputShow.val(this.getAttribute('data-show-value'));
						EList.SetHiddenFieldVal(input, this.getAttribute('data-value'));
						EList.SetShowFieldVal(inputShow, this.getAttribute('data-show-value'), this.getAttribute('data-show-group'));
				});
			}
			
			$(this).attr('data-autoinsert-init', 1);
			$(this).attr('data-autoinsert-index', ind);
		});		
	},
	
	OnFieldFocus: function(objInput)
	{
		$(objInput).unbind('click').bind('click', function(){
			var input = this;
			/*var parentTd = $(input).closest('td');
			parentTd.css('width', parentTd[0].offsetWidth - 6);*/
			var parent = $(input).closest('tr');
			var pSelect = parent.find('select[name^="FIELDS_LIST["]');
			//var inputVal = $('input[name="'+input.name.replace('FIELDS_LIST_SHOW', 'SETTINGS[FIELDS_LIST]')+'"]', parent)[0];
			var inputVal = EList.GetValInputFromShowInput(input);
			//$(input).css('visibility', 'hidden');
			var select = $(pSelect).clone();
			var options = select[0].options;
			for(var i=0; i<options.length; i++)
			{
				if(inputVal.value==options.item(i).value) options.item(i).selected = true;
			}
			
			var chosenId = 'kda_select_chosen';
			$('#'+chosenId).remove();
			var offset = $(input).offset();
			var div = $('<div></div>');
			div.attr('id', chosenId);
			div.css({
				position: 'absolute',
				left: offset.left,
				top: offset.top,
				//width: $(input).width() + 27
				width: $(input).width() + 1
			});
			div.append(select);
			$('body').append(div);
			
			//select.insertBefore($(input));
			if(typeof select.chosen == 'function') select.chosen({search_contains: true});
			select.bind('change', function(){
				if(this.value=='new_prop')
				{
					var ind = this.name.replace(/^.*\[(\d+)\]$/, '$1');
					var ptable = $('.kda-ie-tbl:eq('+ind+')');
					var stInput = $('input[name="SETTINGS[LIST_SETTINGS]['+ind+'][SET_TITLES]"]', ptable);
					var propName = '';
					if(stInput.length > 0 && stInput.val().length > 0)
					{
						var rowIndex = parseInt(stInput.val()) + 1;
						var cellIndex = parseInt(inputVal.name.replace(/^.*\[(\d+)(_\d+)?\]$/, '$1')) + 1;
						propName = $('table.list tr:eq('+rowIndex+') td:eq('+cellIndex+') .cell_inner', ptable).html();
					}
					
					
					var option = options.item(0);
					var dialog = new BX.CAdminDialog({
						'title':BX.message("KDA_IE_POPUP_NEW_PROPERTY_TITLE"),
						'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_new_property.php?FIELD_NAME='+encodeURIComponent(/*input.name*/input.id)+'&IBLOCK_ID='+ptable.attr('data-iblock-id')+'&PROP_NAME='+encodeURIComponent(propName),
						'width':'600',
						'height':'400',
						'resizable':true});
					EList.newPropDialog = dialog;
					EList.NewPropDialogButtonsSet();
						
					BX.addCustomEvent(dialog, 'onWindowRegister', function(){
						$('input[type=checkbox]', this.DIV).each(function(){
							BX.adminFormTools.modifyCheckbox(this);
						});
					});
						
					dialog.Show();
				}
				else
				{
					var option = options.item(select[0].selectedIndex);
				}
				if(option.value)
				{
					/*input.value = option.text;
					input.title = option.text;
					inputVal.value = option.value;*/
					EList.SetHiddenFieldVal(inputVal, option.value);
					EList.SetShowFieldVal(input, option.text, option.parentNode.getAttribute('label'));
				}
				else
				{
					/*input.value = '';
					input.title = '';
					inputVal.value = '';*/
					EList.SetHiddenFieldVal(inputVal, '');
					EList.SetShowFieldVal(input, '');
				}
				if(typeof select.chosen == 'function') select.chosen('destroy');
				//select.remove();
				$('#'+chosenId).remove();
				//$(input).css('visibility', 'visible');
				//parentTd.css('width', 'auto');
			});
			
			$('body').one('click', function(e){
				e.stopPropagation();
				return false;
			});
			var chosenDiv = select.next('.chosen-container')[0];
			$('a:eq(0)', chosenDiv).trigger('mousedown');
			
			var lastClassName = chosenDiv.className;
			var interval = setInterval( function() {   
				   var className = chosenDiv.className;
					if (className !== lastClassName) {
						select.trigger('change');
						lastClassName = className;
						clearInterval(interval);
					}
				},30);
		});
	},
	
	NewPropDialogButtonsSet: function(fireEvents)
	{
		var dialog = this.newPropDialog;
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					var form = document.getElementById('newPropertyForm');
					if($.trim(form['FIELD[NAME]'].value).length==0)
					{
						form['FIELD[NAME]'].style.border = '1px solid red';
						return;
					}
					else
					{
						form['FIELD[NAME]'].style.border = '';
					}
					
					this.disableUntilError();
					this.parentWindow.PostParameters();
					//this.parentWindow.Close();
				}
			})
		]);
		
		if(fireEvents)
		{
			BX.onCustomEvent(dialog, 'onWindowRegister');
		}
	},
	
	CreateNewProperties: function(listIndex)
	{
		var ptable = $('.kda-ie-tbl[data-list-index='+listIndex+']');
		var table = $('table.list', ptable);
		var tds = $('td.kda-ie-field-select', table);
		var l = $('.slevel[data-level-type="headers"]', table);
		if(l.length==0) return;
		
		var items = [];
		var tds2 = l.closest('td').nextAll('td');
		for(var i=0; i<tds2.length; i++)
		{
			var input = tds.eq(i).find('input[name^="SETTINGS[FIELDS_LIST]['+listIndex+']["]:first');
			var text = $(tds2[i]).text();
			if(input.val().length==0 && text.length > 0)
			{
				items.push({index: i, text: text});
			}
		}
		if(items.length > 0)
		{
			var dialogParams = {
				'title':BX.message("KDA_IE_NEW_PROPS_TITLE"),
				'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_new_properties.php?list_index='+listIndex+'&IBLOCK_ID='+ptable.attr('data-iblock-id'),
				'content_post': {items: items},
				'width':'700',
				'height':'430',
				'resizable':true
			};
			var dialog = new BX.CAdminDialog(dialogParams);
				
			dialog.SetButtons([
				dialog.btnCancel,
				new BX.CWindowButton(
				{
					title: BX.message('JS_CORE_WINDOW_SAVE'),
					id: 'savebtn',
					name: 'savebtn',
					className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
					action: function () {
						this.disableUntilError();
						this.parentWindow.PostParameters();
					}
				})
			]);
				
			BX.addCustomEvent(dialog, 'onWindowRegister', function(){
				$('input[type=checkbox]', this.DIV).each(function(){
					BX.adminFormTools.modifyCheckbox(this);
				});
				if(typeof $('.kda-ie-newprops-mchosen select').chosen == 'function')
				{
					$('.kda-ie-newprops-mchosen select').chosen({'placeholder_text':BX.message("KDA_IE_NP_FIELDS_FOR_UPDATE")});
				}
			});
				
			dialog.Show();
		}
	},
	
	OnAfterAddNewProperties: function(listIndex, iblockId, result)
	{
		var ptable = $('.kda-ie-tbl[data-list-index='+listIndex+']');
		var form = ptable.closest('form')[0];
		var post = {
			'MODE': 'AJAX',
			'ACTION': 'GET_SECTION_LIST',
			'IBLOCK_ID': iblockId,
			'PROFILE_ID': form.PROFILE_ID.value
		}
		$.post(window.location.href, post, function(data){			
			ptable.find('select[name^="FIELDS_LIST["]').each(function(){
				var fields = $(data).find('select[name=fields]');
				fields.attr('name', this.name);
				$(this).replaceWith(fields);
			});
		});
		
		if(typeof result == 'object')
		{
			$('table.list td.kda-ie-field-select', ptable).each(function(index){
				if(!result[index]) return;
				var td = $(this);
				//$('input[name="FIELDS_LIST_SHOW['+listIndex+']['+index+']"]', td).val(result[index]['NAME']);
				//$('input[name="SETTINGS[FIELDS_LIST]['+listIndex+']['+index+']"]', td).val(result[index]['ID']);
				EList.SetHiddenFieldVal($('input[name="SETTINGS[FIELDS_LIST]['+listIndex+']['+index+']"]', td), result[index]['ID']);
				EList.SetShowFieldVal($('#field-list-show-'+listIndex+'-'+index, td), result[index]['NAME']);
				td.addClass('kda-ie-field-select-highligth');
				setTimeout(function(){
					td.removeClass('kda-ie-field-select-highligth');
				}, 2000);
			});
		}
		
		BX.WindowManager.Get().Close();
	},
	
	SetOldTitles: function(listIndex, titles)
	{
		if(!this.oldTitles) this.oldTitles = {};
		this.oldTitles[listIndex] = titles;
	},
	
	BindFieldsToHeaders: function(listIndex)
	{
		var settingsBlock = $('.kda-ie-tbl[data-list-index='+listIndex+']').find('.list-settings');
		
		var inputName = 'SETTINGS[LIST_SETTINGS]['+listIndex+'][BIND_FIELDS_TO_HEADERS]';
		var input = $('input[name="'+inputName+'"]', settingsBlock);
		if(input.length == 0)
		{
			settingsBlock.append('<input type="hidden" name="'+inputName+'" value="">');
			input = $('input[name="'+inputName+'"]', settingsBlock);
		}
		input.val(1);
	},
	
	UnbindFieldsToHeaders: function(listIndex)
	{
		var settingsBlock = $('.kda-ie-tbl[data-list-index='+listIndex+']').find('.list-settings');
		var inputName = 'SETTINGS[LIST_SETTINGS]['+listIndex+'][BIND_FIELDS_TO_HEADERS]';
		$('input[name="'+inputName+'"]', settingsBlock).remove();
	},
	
	HideEmptyColumns: function(listIndex)
	{
		var settingsBlock = $('.kda-ie-tbl[data-list-index='+listIndex+']').find('.list-settings');
		
		var inputName = 'SETTINGS[LIST_SETTINGS]['+listIndex+'][HIDE_EMPTY_COLUMNS]';
		var input = $('input[name="'+inputName+'"]', settingsBlock);
		if(input.length == 0)
		{
			settingsBlock.append('<input type="hidden" name="'+inputName+'" value="">');
			input = $('input[name="'+inputName+'"]', settingsBlock);
		}
		input.val(1);
		
		var tbl = $('.kda-ie-tbl[data-list-index='+listIndex+'] table.list');
		$('.kda-ie-field-select', tbl).each(function(index){
			var inputs = $('input[name^="SETTINGS[FIELDS_LIST]"]', this);
			var empty = true;
			for(var i=0; i<inputs.length; i++)
			{
				if($.trim(inputs[i].value).length > 0) empty = false;
			}
			if(empty)
			{
				$(this).addClass('kda-ie-field-select-empty');
				$('>td:eq('+(index+1)+')', $('>tr', tbl)).hide();
				$('>td:eq('+(index+1)+')', $('>tbody >tr', tbl)).hide();
			}
		});
		$(window).trigger('resize');
	},
	
	ShowEmptyColumns: function(listIndex)
	{
		var settingsBlock = $('.kda-ie-tbl[data-list-index='+listIndex+']').find('.list-settings');
		var inputName = 'SETTINGS[LIST_SETTINGS]['+listIndex+'][HIDE_EMPTY_COLUMNS]';
		$('input[name="'+inputName+'"]', settingsBlock).remove();
		var tbl = $('.kda-ie-tbl[data-list-index='+listIndex+'] table.list');
		$('.kda-ie-field-select', tbl).each(function(index){
			if($(this).hasClass('kda-ie-field-select-empty'))
			{
				$(this).removeClass('kda-ie-field-select-empty');
				$('>td:eq('+(index+1)+')', $('>tr', tbl)).show();
				$('>td:eq('+(index+1)+')', $('>tbody >tr', tbl)).show();
			}
		});
		$(window).trigger('resize');
	},
	
	ShowLineActions: function(input, firstInit)
	{
		var arKeys = input.name.substr(0, input.name.length - 1).split('][');
		var action = arKeys[arKeys.length - 1];
		var tblIndex = arKeys[arKeys.length - 2];
		var title = '';
		var fieldGroup = '';
		if(action.indexOf('SET_SECTION_')==0) fieldGroup = 'SECTION';
		if(action.indexOf('SET_PROPERTY_')==0) fieldGroup = 'PROPERTY';
		if(fieldGroup.length > 0 && admKDAMessages.lineActions[fieldGroup])
		{
			if(admKDAMessages.lineActions[fieldGroup][action]) title = admKDAMessages.lineActions[fieldGroup][action].TITLE;
			var style = input.value;
			if(!style) return;
			var level = this.GetActionLevel(action);
			var label = title.substr(0, 1);
			if(fieldGroup=='SECTION') label = 'P'+(level > 0 ? level : '');
			var tbl = $(input).closest('.kda-ie-tbl');
			var cellNameIndex = 0;
			var inputCellName = 'SETTINGS[LIST_SETTINGS]['+tblIndex+'][SECTION_NAME_CELL]';
			var inputCellNameObj = $('input[name="'+inputCellName+'"]', tbl);
			if(inputCellNameObj.length > 0)
			{
				cellNameIndex = inputCellNameObj.val();
			}
			tbl.find('td.line-settings').each(function(){
				var td = $(this);
				var tr = td.closest('tr');
				if($('.cell_inner:not(:empty):eq(0)', tr).attr('data-style') == style)
				{
					var html = '<span class="slevel" data-level="'+level+'" title="'+title+'">'+label+'</span>';
					if(td.find('.slevel').length > 0)
					{
						td.find('.slevel').replaceWith(html);
					}
					else
					{
						td.append(html);
					}
					if(cellNameIndex > 0)
					{
						$('td:eq('+cellNameIndex+')', tr).addClass('section_name_cell');
					}
					
					$('td:gt(0)', tr).bind('contextmenu', function(e){
						EList.SetCurrentCell(this);
						if(this.OPENER) this.OPENER.Toggle();
						else
						{
							var menuItems = [
								{
									TEXT: BX.message("KDA_IE_IS_SECTION_NAME"),
									ONCLICK: 'EList.ChooseSectionNameCell();'
								},
								{
									TEXT: BX.message("KDA_IE_SECTION_FIELD_SETTINGS"),
									ONCLICK: 'EList.ShowSectionFieldSettings("'+level+'");'
								},
								{
									TEXT: BX.message("KDA_IE_RESET_ACTION"),
									ONCLICK: 'EList.UnsetSectionNameCell();'
								}
							];
							BX.adminShowMenu(this, menuItems, {active_class: "bx-adm-scale-menu-butt-active"});
						}
						
						e.stopPropagation();
						return false;
					});
				}
				else
				{
					if(td.find('.slevel[data-level='+level+']').length > 0)
					{
						$('td.section_name_cell', tr).removeClass('section_name_cell');
						$('td:gt(0)', tr).unbind('contextmenu');
						td.find('.slevel').remove();
					}
				}
			});
		}
		else if(action.indexOf('SECTION_NAME_CELL')==0)
		{
			var tdIndex = parseInt(input.value);
			var td = $('.kda-ie-tbl[data-list-index='+tblIndex+'] input[name^="SETTINGS[IMPORT_LINE]"]:eq(0)').closest('tr').find('td:eq('+tdIndex+')');
			if(td.length > 0)
			{
				td = td[0];
				EList.SetCurrentCell(td);
				EList.ChooseSectionNameCell();
			}
		}
		else if(action.indexOf('SET_TITLES')==0 || action.indexOf('SET_HINTS')==0)
		{
			var levelType = (action.indexOf('SET_TITLES')==0 ? 'headers' : 'hints');
			var lineIndex = input.value;
			var tbl = $(input).closest('.kda-ie-tbl');
			$('.slevel[data-level-type="'+levelType+'"]', tbl).remove();
			var td = $('input[name="SETTINGS[IMPORT_LINE]['+tblIndex+']['+lineIndex+']"]', tbl).closest('td.line-settings');
			if(td.length > 0)
			{
				var html = '<span class="slevel" data-level-type="'+levelType+'"></span>';
				if(td.find('.slevel').length > 0)
				{
					td.find('.slevel').replaceWith(html);
				}
				else
				{
					td.append(html);
				}
				var tr = td.closest('tr');
				tr.addClass('kda-ie-tbl-'+levelType);
				if(levelType=='headers')
				{
					tr.find('>td:gt(0)').each(function(tdIndex){
						var tdInput = $('<input type="hidden" name="SETTINGS[TITLES_LIST]['+tblIndex+']['+tdIndex+']" value="">');
						tdInput.val($.trim($('.cell_inner', this).text()).toLowerCase().replace(/[\r\n\s]+/g, ' '));
						$(this).prepend(tdInput);
					});
					var obj = this;
					if(firstInit && obj.oldTitles && obj.oldTitles[tblIndex])
					{
						/*var bfInput = $('input[name="SETTINGS[LIST_SETTINGS]['+tblIndex+'][BIND_FIELDS_TO_HEADERS]"]', tbl);
						if(bfInput.length > 0)
						{
							var tds = $('table.list td.kda-ie-field-select', tbl);
							var oldTds = tds.clone();
							$('table.list td.kda-ie-field-select', tbl).each(function(tdIndex){
								var cutTitle = $('input[name="SETTINGS[TITLES_LIST]['+tblIndex+']['+tdIndex+']"]', tr).val();
								if(!obj.oldTitles[tblIndex][tdIndex] || obj.oldTitles[tblIndex][tdIndex]!=cutTitle)
								{
									var oldIndex = -1;
									for(var i in obj.oldTitles[tblIndex])
									{
										if(obj.oldTitles[tblIndex][i]==cutTitle) oldIndex = i;
									}
									if(oldIndex>=0)
									{
										$('>div', this).remove();
										$('>div', oldTds[oldIndex]).clone().appendTo(this);
									}
									else
									{
										$('>div:gt(0)', this).remove();
										EList.SetHiddenFieldVal($('>div input', this), '');
										EList.SetShowFieldVal($('>div span.fieldval', this), '');
									}
								}
							});
							this.OnAfterColumnsMove(tds.eq(0).closest('tr'));
						}*/
					}
				}
			}
		}
	},
	
	GetActionLevel: function(action)
	{
		var level = 0;
		if(action.indexOf('SET_SECTION_')==0)
		{
			level = parseInt(action.substr(12));
			if(action.substr(12)=='PATH') level = 0;
		}
		else if(action.indexOf('SET_PROPERTY_')==0)
		{
			level = 'P'+parseInt(action.substr(13));
		}
		return level;
	},
	
	RemoveLineActions: function(input)
	{
		var arKeys = input.name.substr(0, input.name.length - 1).split('][');
		var action = arKeys[arKeys.length - 1];
		var k2 = arKeys[arKeys.length - 2];
		if(action.indexOf('SET_SECTION_')==0 || action.indexOf('SET_PROPERTY_')==0)
		{
			var level = this.GetActionLevel(action);
			var labels = $(input).closest('.kda-ie-tbl').find('.slevel[data-level='+level+']');
			var trs = labels.closest('tr');
			$('td.section_name_cell:eq(0)', trs).removeClass('section_name_cell');
			/*var td = $('td.section_name_cell:eq(0)', trs);
			if(td.length > 0)
			{
				this.SetCurrentCell(td[0]);
				this.UnsetSectionNameCell();
			}*/
			
			$('td:gt(0)', trs).unbind('contextmenu');
			labels.remove();
		}
		else if(action.indexOf('SET_TITLES')==0 || action.indexOf('SET_HINTS')==0)
		{
			var levelType = (action.indexOf('SET_TITLES')==0 ? 'headers' : 'hints');
			var labels = $(input).closest('.kda-ie-tbl').find('.slevel[data-level-type="'+levelType+'"]');
			var trs = labels.closest('tr');
			trs.removeClass('kda-ie-tbl-'+levelType);
			trs.find('>td input[name^="SETTINGS[TITLES_LIST]["]').remove();
			labels.remove();
		}
		$(input).remove();
	},
	
	SetLineAction: function(action, k1, k2)
	{
		var inputLine = $('.kda-ie-tbl input[name="SETTINGS[IMPORT_LINE]['+k1+']['+k2+']"]:eq(0)');
		
		if(action == 'REMOVE_ACTION')
		{
			var levelObj = $(inputLine).closest('td').find('.slevel');
			if(levelObj.attr('data-level-type')=='headers')
			{
					var input = $(inputLine).closest('.kda-ie-tbl').find('input[name="SETTINGS[LIST_SETTINGS]['+k1+'][SET_TITLES]"]');
					if(input.length > 0) this.RemoveLineActions(input[0]);
			}
			else if(levelObj.attr('data-level-type')=='hints')
			{
					var input = $(inputLine).closest('.kda-ie-tbl').find('input[name="SETTINGS[LIST_SETTINGS]['+k1+'][SET_HINTS]"]');
					if(input.length > 0) this.RemoveLineActions(input[0]);
			}
			else
			{
				var level = $(inputLine).closest('td').find('.slevel').attr('data-level');
				if(level.length > 0 && level=='0') level = 'PATH';
				var paramName = 'SET_SECTION_'+level;
				if(level!='PATH' && level.substr(0, 1)=='P') paramName = 'SET_PROPERTY_'+level.substr(1);
				if(level)
				{
					var input = $(inputLine).closest('.kda-ie-tbl').find('input[name="SETTINGS[LIST_SETTINGS]['+k1+']['+paramName+']"]');
					if(input.length > 0) this.RemoveLineActions(input[0]);
				}
			}
			return;
		}
		
		if(action.indexOf('SET_SECTION_')==0 || action.indexOf('SET_PROPERTY_')==0)
		{
			var tr = inputLine.closest('tr');
			var style = $('.cell_inner:not(:empty):eq(0)', tr).attr('data-style');
			
			var settingsBlock = inputLine.closest('.kda-ie-tbl').find('.list-settings');
			$('input[name^="SETTINGS[LIST_SETTINGS]['+k1+'][SET_SECTION_"], input[name^="SETTINGS[LIST_SETTINGS]['+k1+'][SET_PROPERTY_"]', settingsBlock).each(function(){
				if(this.value == style)
				{
					EList.RemoveLineActions(this);
				}
			});
			
			var inputName = 'SETTINGS[LIST_SETTINGS]['+k1+']['+action+']';
			var input = $('input[name="'+inputName+'"]', settingsBlock);
			if(input.length == 0)
			{
				settingsBlock.append('<input type="hidden" name="'+inputName+'" value="">');
				input = $('input[name="'+inputName+'"]', settingsBlock);
			}
			input.val(style);
			this.ShowLineActions(input[0]);
		}
		
		if(action.indexOf('SET_TITLES')==0 || action.indexOf('SET_HINTS')==0)
		{
			var input = $(inputLine).closest('.kda-ie-tbl').find('input[name="SETTINGS[LIST_SETTINGS]['+k1+']['+action+']"]');
			if(input.length > 0) this.RemoveLineActions(input[0]);
			
			var settingsBlock = inputLine.closest('.kda-ie-tbl').find('.list-settings');
			var inputName = 'SETTINGS[LIST_SETTINGS]['+k1+']['+action+']';
			var input = $('input[name="'+inputName+'"]', settingsBlock);
			if(input.length == 0)
			{
				settingsBlock.append('<input type="hidden" name="'+inputName+'" value="">');
				input = $('input[name="'+inputName+'"]', settingsBlock);
			}
			input.val(k2);
			this.ShowLineActions(input[0]);
			this.SetFieldAutoInsert(inputLine.closest('table').find('tr:first'), true)
		}
	},
	
	SetCurrentCell: function(td)
	{
		this.currentCell = td;
	},
	
	GetCurrentCell: function(td)
	{
		return this.currentCell;
	},
	
	ChooseSectionNameCell: function()
	{
		var td = this.GetCurrentCell();
		var tr = $(td).closest('tr')[0];
		var table = $(td).closest('table');
		var tdIndex = 0;
		for(var i=0; i<tr.cells.length; i++)
		{
			if(tr.cells[i]==td) tdIndex = i;
		}
		table.find('.slevel').each(function(){
			var tr = $(this).closest('tr');
			$('td.section_name_cell', tr).removeClass('section_name_cell');
			if(tdIndex > 0) $('td:eq('+tdIndex+')', tr).addClass('section_name_cell');
		});
		
		
		var inputName2 = $(tr).find('input[name^="SETTINGS[IMPORT_LINE]"]:eq(0)').attr('name');
		var arKeys = inputName2.substr(22, inputName2.length - 23).split('][');
		var k1 = arKeys[0];
		
		var settingsBlock = $(td).closest('.kda-ie-tbl').find('.list-settings');
		var inputName = 'SETTINGS[LIST_SETTINGS]['+k1+'][SECTION_NAME_CELL]';
		
		var input = $('input[name="'+inputName+'"]', settingsBlock);
		if(input.length == 0)
		{
			settingsBlock.append('<input type="hidden" name="'+inputName+'" value="">');
			input = $('input[name="'+inputName+'"]', settingsBlock);
		}
		input.val(tdIndex);
	},
	
	UnsetSectionNameCell: function()
	{
		var td = this.GetCurrentCell();
		var tr = $(td).closest('tr')[0];
		var table = $(td).closest('table');
		var tdIndex = 0;
		for(var i=0; i<tr.cells.length; i++)
		{
			if(tr.cells[i]==td) tdIndex = i;
		}
		
		var inputName2 = $(tr).find('input[name^="SETTINGS[IMPORT_LINE]"]:eq(0)').attr('name');
		var arKeys = inputName2.substr(22, inputName2.length - 23).split('][');
		var k1 = arKeys[0];
		var settingsBlock = $(td).closest('.kda-ie-tbl').find('.list-settings');
		var inputName = 'SETTINGS[LIST_SETTINGS]['+k1+'][SECTION_NAME_CELL]';
		var input = $('input[name="'+inputName+'"]', settingsBlock);
		if(input.length == 0 || input.val()!=tdIndex) return;
			
		table.find('.slevel').each(function(){
			var tr = $(this).closest('tr');
			$('td.section_name_cell', tr).removeClass('section_name_cell');
		});
		input.remove();
	},
	
	ShowSectionFieldSettings: function(level)
	{
		var td = this.GetCurrentCell();
		var listIndex = $(td).closest('.kda-ie-tbl').attr('data-list-index');
		var tr = $(td).closest('tr')[0];
		var table = $(td).closest('table')[0];
		
		var linkId = 'field_settings_'+listIndex+'___'+level;
		if($('#'+linkId).length==0)
		{
			var settingsBlock = $(td).closest('.kda-ie-tbl').find('.list-settings');
			settingsBlock.append('<a href="javascript:void(0)" id="'+linkId+'" onclick="EList.ShowFieldSettings(this);"><input name="EXTRASETTINGS['+listIndex+'][__'+level+']" value="" type="hidden"></a>');
		}
		var field = (level > 0 ? 'SECTION_SEP_NAME' : 'SECTION_SEP_NAME_PATH');
		if(level.substr(0, 1)=='P') field = 'IP_PROP'+level.substr(1);
		this.ShowFieldSettings($('#'+linkId)[0], 'SETTINGS[FIELDS_LIST]['+listIndex+'][__'+level+']', field);
		
		/*var td = this.GetCurrentCell();
		var tr = $(td).closest('tr')[0];
		var table = $(td).closest('table')[0];
		var tdIndex = 0;
		for(var i=0; i<tr.cells.length; i++)
		{
			if(tr.cells[i]==td) tdIndex = i;
		}
		var listIndex = $(td).closest('.kda-ie-tbl').attr('data-list-index');
		var btn = $('tr:first td:eq('+tdIndex+') .field_settings:eq(0)', table);
		if(btn.length > 0)
		{
			this.ShowFieldSettings(btn[0], 'SETTINGS[FIELDS_LIST]['+listIndex+'][SECTION_'+(tdIndex-1)+']', 'SECTION_SEP_NAME');
		}*/
	},
	
	SetWidthList: function()
	{
		$('.kda-ie-tbl:not(.empty) div.set').each(function(){
			var div = $(this);
			div.css('width', 0);
			div.prev('.set_scroll').css('width', 0);
			var timer = setInterval(function(){
				var width = div.parent().width();
				if(width > 0)
				{
					div.css('width', width);
					div.prev('.set_scroll').css('width', width).find('>div').css('width', div.find('>table.list').width());
					clearInterval(timer);
				}
			}, 100);
			setTimeout(function(){clearInterval(timer);}, 3000);
		});
	},
	
	ToggleSettings: function(btn)
	{
		var table = $(btn).closest('.kda-ie-tbl');
		var tr = table.find('tr.settings');
		if(tr.is(':visible'))
		{
			tr.hide();
			$(btn).removeClass('open');
		}
		else
		{
			if(!table.attr('data-fields-init'))
			{
				this.SetFieldValues(table);
				table.attr('data-fields-init', 1);
				
				var list = table.attr('data-list-index');
				var hecInput = $('input[name="SETTINGS[LIST_SETTINGS]['+list+'][HIDE_EMPTY_COLUMNS]"]', table);
				if(hecInput.val()=='1')
				{
					this.HideEmptyColumns(list);
				}
			}
			
			tr.show();
			$(btn).addClass('open');
		}
		$(window).trigger('resize');		
	},

	ShowFull: function(btn)
	{
		var tbl = $(btn).closest('.kda-ie-tbl');
		var list = tbl.attr('data-list-index');
		var colCount = Math.max(1, $('table.list tr:eq(0) > td', tbl).length - 1);
		var post = $(btn).closest('form').serialize() + '&ACTION=SHOW_FULL_LIST&LIST_NUMBER=' + list + '&COUNT_COLUMNS=' + colCount;
		var wait = BX.showWait();
		$.post(window.location.href, post, function(data){
			data = $(data);
			var chb = $('input[type=checkbox][name^="SETTINGS[CHECK_ALL]"]', tbl);
			/*if(chb.length > 0)
			{
				if(chb[0].checked)
				{
					data.find('input[type=checkbox]').prop('checked', true);
				}
				else
				{
					data.find('input[type=checkbox]').prop('checked', false);
				}
			}*/
			$('table.list', tbl).append(data);
			/*$('table.list input[type=checkbox]', tbl).each(function(){
				BX.adminFormTools.modifyCheckbox(this);
			});*/
			EList.InitLines(list);
			$(window).trigger('resize');
			BX.closeWait(null, wait);
		});
		$(btn).hide();
	},
	
	ApplyToAllLists: function(link)
	{
		var tbl = $(link).closest('.kda-ie-tbl');
		var tbls = tbl.parent().find('.kda-ie-tbl').not(tbl);
		var form = tbl.closest('form')[0];
		
		/*var post = {
			'MODE': 'AJAX',
			'ACTION': 'APPLY_TO_LISTS',
			'PROFILE_ID': form.PROFILE_ID.value,
			'LIST_FROM': tbl.attr('data-list-index')
		}
		post.LIST_TO = [];
		for(var i=0; i<tbls.length; i++)
		{
			post.LIST_TO.push($(tbls[i]).attr('data-list-index'));
		}
		$.post(window.location.href, post, function(data){});*/
		
		var ts = tbl.find('.kda-ie-field-select');
		var cha = tbl.find('input[type="checkbox"][name^="SETTINGS[CHECK_ALL]"]');
		var chl = tbl.find('input[type="checkbox"][name^="SETTINGS[IMPORT_LINE]"]');
		for(var i=0; i<tbls.length; i++)
		{
			var tss = $('.kda-ie-field-select', tbls[i]);
			for(var j=0; j<ts.length; j++)
			{
				if(!tss[j]) continue;
				/*var c1 = $('input.fieldval', ts[j]).length;
				var c2 = $('input.fieldval', tss[j]).length;*/
				var c1 = $('span.fieldval', ts[j]).length;
				var c2 = $('span.fieldval', tss[j]).length;
				if(c2 < c1)
				{
					for(var k=0; k<c1-c2; k++)
					{
						$('.kda-ie-add-load-field', tss[j]).trigger('click');
					}
				}
				else if(c2 > c1)
				{
					for(var k=0; k<c2-c1; k++)
					{
						$('.field_delete:last', tss[j]).trigger('click');
					}
				}
				
				var fts = $('input[name^="SETTINGS[FIELDS_LIST]"]', ts[j]);
				//var fts2 = $('input[name^="FIELDS_LIST_SHOW"]', ts[j]);
				var fts2 = $('span.fieldval', ts[j]);
				var fts2s = $('a.field_settings', ts[j]);
				var fts2si = $('input[name^="EXTRASETTINGS"]', fts2s);
				var ftss = $('input[name^="SETTINGS[FIELDS_LIST]"]', tss[j]);
				//var ftss2 = $('input[name^="FIELDS_LIST_SHOW"]', tss[j]);
				var ftss2 = $('span.fieldval', tss[j]);
				var ftss2s = $('a.field_settings', tss[j]);
				var ftss2si = $('input[name^="EXTRASETTINGS"]', ftss2s);
				for(var k=0; k<ftss.length; k++)
				{
					if(fts[k])
					{
						//ftss[k].value = fts[k].value;
						//ftss2[k].value = fts2[k].value;
						EList.SetHiddenFieldVal(ftss[k], fts[k].value);
						EList.SetShowFieldVal(ftss2[k], fts2[k].innerHTML);
						ftss2si[k].value = fts2si[k].value;
						if($(fts2s[k]).hasClass('inactive')) $(ftss2s[k]).addClass('inactive');
						else $(ftss2s[k]).removeClass('inactive');
					}
				}
			}
			
			var chas = $('input[type="checkbox"][name^="SETTINGS[CHECK_ALL]"]', tbls[i]);
			if(cha[0] && chas[0])
			{
				chas[0].checked = cha[0].checked;
			}
			var chls = $('input[type="checkbox"][name^="SETTINGS[IMPORT_LINE]"]', tbls[i]);
			for(var j=0; j<chl.length; j++)
			{
				if(!chls[j]) continue;
				chls[j].checked = chl[j].checked;
			}
		}
	},
	
	OnAfterAddNewProperty: function(fieldName, propId, propName, iblockId)
	{
		//var field = $('input[name="'+fieldName+'"]');
		var field = $('#'+fieldName);
		var form = field.closest('form')[0];
		var post = {
			'MODE': 'AJAX',
			'ACTION': 'GET_SECTION_LIST',
			'IBLOCK_ID': iblockId,
			'PROFILE_ID': form.PROFILE_ID.value
		}
		var ptable = $(field).closest('.kda-ie-tbl');
		$.post(window.location.href, post, function(data){			
			ptable.find('select[name^="FIELDS_LIST["]').each(function(){
				var fields = $(data).find('select[name=fields]');
				fields.attr('name', this.name);
				$(this).replaceWith(fields);
			});
		});
		//field.val(propName);
		EList.SetHiddenFieldVal(EList.GetValInputFromShowInput(field[0]), propId);
		EList.SetShowFieldVal(field, propName);
		/*$('input[name="'+fieldName.replace('FIELDS_LIST_SHOW', 'SETTINGS[FIELDS_LIST]')+'"]', ptable).val(propId);
		var input = EList.GetValInputFromShowInput(field[0]);
		$(input).val(propId);*/
		
		BX.WindowManager.Get().Close();
		
		var td = field.closest('td');
		td.addClass('kda-ie-field-select-highligth');
		setTimeout(function(){
			td.removeClass('kda-ie-field-select-highligth');
		}, 2000);
	},
	
	ChooseIblock: function(select)
	{
		var form = $(select).closest('form')[0];
		var ptable = $(select).closest('.kda-ie-tbl');
		var post = {
			'MODE': 'AJAX',
			'ACTION': 'GET_SECTION_LIST',
			'IBLOCK_ID': select.value,
			'PROFILE_ID': form.PROFILE_ID.value,
			'LIST_INDEX': ptable.attr('data-list-index')
		}
		$.post(window.location.href, post, function(data){
			var sections = $(data).find('select[name=sections]');
			var sectSelect = $(select).closest('table').find('select[name="'+select.name.replace('[IBLOCK_ID]', '[SECTION_ID]')+'"]');
			sections.attr('name', sectSelect.attr('name'));
			if(typeof sectSelect.chosen == 'function') sectSelect.chosen('destroy');
			sectSelect.replaceWith(sections);
			if(typeof sections.chosen == 'function') sections.chosen({search_contains: true});
			
			ptable.find('select[name^="FIELDS_LIST["]').each(function(){
				var fields = $(data).find('select[name=fields]');
				fields.attr('name', this.name);
				$(this).replaceWith(fields);
				EList.SetFieldValues(ptable, true);
			});
			
			var setDiv = $('.addsettings', ptable);
			var searchSection = $(data).find('select[name=search_sections]');
			var searchSectionSelect = setDiv.find('select[name^="SETTINGS[SEARCH_SECTIONS]["]');
			searchSection.attr('name', searchSectionSelect.attr('name'));
			if(typeof searchSectionSelect.chosen == 'function') searchSectionSelect.chosen('destroy');
			searchSectionSelect.replaceWith(searchSection);
			if(typeof searchSection.chosen == 'function') searchSection.chosen({search_contains: true, placeholder_text: BX.message("KDA_IE_SELECT_NOT_CHOSEN")});
			
			var uid = $(data).find('select[name=element_uid]');
			var uidSelect = setDiv.find('select[name^="SETTINGS[LIST_ELEMENT_UID]["]');
			uid.attr('name', uidSelect.attr('name'));
			if(typeof uidSelect.chosen == 'function') uidSelect.chosen('destroy');
			uidSelect.replaceWith(uid);
			if(typeof uid.chosen == 'function') uid.chosen({search_contains: true, placeholder_text: BX.message("KDA_IE_SELECT_NOT_CHOSEN")});
			
			var needShow = (setDiv.find('input[name^="SETTINGS[CHANGE_ELEMENT_UID]["]:checked').length > 0);
			var uidSku = $(data).find('select[name=element_uid_sku]');
			var uidSkuSelect = setDiv.find('select[name^="SETTINGS[LIST_ELEMENT_UID_SKU]["]');
			uidSku.attr('name', uidSkuSelect.attr('name'));
			if(typeof uidSkuSelect.chosen == 'function') uidSkuSelect.chosen('destroy');
			uidSkuSelect.replaceWith(uidSku);
			if(typeof uidSku.chosen == 'function') uidSku.chosen({search_contains: true, placeholder_text: BX.message("KDA_IE_SELECT_NOT_CHOSEN")});
			uidSku.closest('tr').css('display', ((needShow && $('option', uidSku).length > 0) ? '' : 'none'));
			
			ptable.find('table.list tbody, table.list tfoot').show();
			ptable.attr('data-iblock-id', select.value);
		});
	},
	
	OnChangeFieldHandler: function(select)
	{
		var val = select.value;
		var link = $(select).next('a.field_settings');
		/*if(val.indexOf("ICAT_PRICE")===0 || val=="ICAT_PURCHASING_PRICE")
		{
			link.removeClass('inactive');
		}
		else
		{
			link.addClass('inactive');
		}*/
	},
	
	AddUploadField: function(link)
	{
		var parent = $(link).closest('.kda-ie-field-select-btns');
		var div = parent.prev('div').clone();
		var input = $('input[name^="SETTINGS[FIELDS_LIST]"]', div)[0];
		//var inputShow = $('input[name^="FIELDS_LIST_SHOW"]', div)[0];
		var inputShow = $('span.fieldval', div)[0];
		var a = $('a.field_settings', div)[0];
		var inputExtra = $('input', a)[0];
		$('.field_insert', div).remove();
		
		var sname = input.name;
		var index = sname.substr(0, sname.length-1).split('][').pop();
		var arIndex = index.split('_');
		if(arIndex.length==1) arIndex[1] = 1;
		else arIndex[1] = parseInt(arIndex[1]) + 1;
		
		input.name = input.name.replace(/\[[\d_]+\]$/, '['+arIndex.join('_')+']');
		//inputShow.name = input.name.replace('SETTINGS[FIELDS_LIST]', 'FIELDS_LIST_SHOW');
		inputShow.id = this.GetShowInputNameFromValInputName(input.name);
		if(arIndex[1] > 1) a.id = a.id.replace(/\_\d+_\d+$/, '_'+arIndex.join('_'));
		else a.id = a.id.replace(/\_\d+$/, '_'+arIndex.join('_'));
		$(a).addClass('inactive');
		inputExtra.name = inputExtra.name.replace(/\[[\d_]+\]$/, '['+arIndex.join('_')+']');
		inputExtra.value = '';
		
		div.insertBefore(parent);
		EList.OnFieldFocus(inputShow);
	},
	
	DeleteUploadField: function(link)
	{
		var parent = $(link).closest('div');
		EList.SetHiddenFieldVal($('input[name^="SETTINGS[FIELDS_LIST]"]', parent), '');
		parent.remove();
	},
	
	ShowFieldSettings: function(btn, name, val)
	{
		//if($(btn).hasClass('inactive')) return;
		if(!name || !val)
		{
			var input = $(btn).prevAll('input[name^="SETTINGS[FIELDS_LIST]"]');
			val = input.val();
			name = input[0].name;
		}
		//var input2 = $(btn).prevAll('input[name^="FIELDS_LIST_SHOW["]');
		var input2 = $(btn).closest('div').find('span.fieldval');
		var input2Val = input2.html();
		if(input2Val==input2.attr('placeholder')) input2Val = '';
		var ptable = $(btn).closest('.kda-ie-tbl');
		var form = $(btn).closest('form')[0];
		var countCols = $(btn).closest('tr').find('.kda-ie-field-select').length;
		
		var dialogParams = {
			//'title':BX.message("KDA_IE_POPUP_FIELD_SETTINGS_TITLE") + (input2.val() ? ' "'+input2.val()+'"' : ''),
			'title':BX.message("KDA_IE_POPUP_FIELD_SETTINGS_TITLE") + (input2Val ? ' "'+input2Val+'"' : ''),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_field_settings.php?field='+val+'&field_name='+name+'&IBLOCK_ID='+ptable.attr('data-iblock-id')+'&PROFILE_ID='+form.PROFILE_ID.value+'&count_cols='+countCols,
			'width':'900',
			'height':'400',
			'resizable':true
		};
		if($('input', btn).length > 0)
		{
			dialogParams['content_url'] += '&return_data=1';
			dialogParams['content_post'] = {'POSTEXTRA': $('input', btn).val()};
		}
		var dialog = new BX.CAdminDialog(dialogParams);
			
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					this.disableUntilError();
					this.parentWindow.PostParameters();
					//this.parentWindow.Close();
				}
			})/*,
			dialog.btnSave*/
		]);
			
		BX.addCustomEvent(dialog, 'onWindowRegister', function(){
			$('input[type=checkbox]', this.DIV).each(function(){
				BX.adminFormTools.modifyCheckbox(this);
			});
			ESettings.BindConversionEvents();
		});
			
		dialog.Show();
	},
	
	ShowListSettings: function(btn)
	{
		var tbl = $(btn).closest('.kda-ie-tbl');
		var post = 'list_index='+tbl.attr('data-list-index');
		var inputs = tbl.find('input[name^="SETTINGS[FIELDS_LIST]"], select[name^="SETTINGS[IBLOCK_ID]"], select[name^="SETTINGS[SECTION_ID]"], input[name^="SETTINGS[ADDITIONAL_SETTINGS]"]');
		for(var i in inputs)
		{
			post += '&'+inputs[i].name+'='+inputs[i].value;
		}
		
		var abtns = tbl.find('a.field_insert');
		var findFields = [];
		for(var i=0; i<abtns.length; i++)
		{
			findFields.push('FIND_FIELDS[]='+$(abtns[i]).attr('data-value'));
		}
		if(findFields.length > 0)
		{
			post += '&'+findFields.join('&');
		}
		
		var dialog = new BX.CAdminDialog({
			'title':BX.message("KDA_IE_POPUP_LIST_SETTINGS_TITLE"),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_list_settings.php',
			'content_post': post,
			'width':'900',
			'height':'400',
			'resizable':true});
			
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					this.disableUntilError();
					this.parentWindow.PostParameters();
					//this.parentWindow.Close();
				}
			})/*,
			dialog.btnSave*/
		]);
			
		BX.addCustomEvent(dialog, 'onWindowRegister', function(){
			$('input[type=checkbox]', this.DIV).each(function(){
				BX.adminFormTools.modifyCheckbox(this);
			});
			if(typeof $('select.kda-chosen-multi').chosen == 'function')
			{
				$('select.kda-chosen-multi').chosen();
			}
		});
			
		dialog.Show();
	},
	
	ChooseColumnForMove: function(btn, e)
	{
		var obj = this;
		var parent = $(btn).closest('.kda-ie-field-select');
		var offset = parent.offset();
		var btnsInnerParent = parent.find('.kda-ie-field-select-btns-inner');
		var width = parent.width() + 4;
		$('body').append('<div id="kda-ie-move-column-abs"></div>');
		$('#kda-ie-move-column-abs').css({
			width: width,
			height: parent.height() + btnsInnerParent.height() + 8,
			top: offset.top,
			left: offset.left
		});
		
		var tr = parent.closest('tr');
		var tds = tr.find('.kda-ie-field-select');
		this.moveColumnsX = [];
		for(var i=0; i<tds.length; i++)
		{
			this.moveColumnsX.push(Math.round($(tds[i]).offset().left));
			if(parent[0]==tds[i])
			{
				this.moveCurrentIndex = i;
			}
		}
		
		this.moveCorrectX = e.pageX - offset.left;
		this.moveCorrectY = e.pageY - offset.top;
		this.moveWidth = width;
		this.moveTds = tds;
		this.movebeginX = offset.left;
		this.moveNewIndex = -1;
		
		$(window).bind('mousemove', function(e){
			return obj.ColumnProccessMove(e);
		});
		
		$(window).bind('mouseup', function(){
			obj.ColumnMoveEnd();
		});
		
		e.stopPropagation();
		return false;
	},
	
	ColumnProccessMove: function(e)
	{
		if(!document.getElementById('kda-ie-move-column-abs')) return;
		var moveObj = $('#kda-ie-move-column-abs');
		var left = e.pageX - this.moveCorrectX;
		var right = left + this.moveWidth;
		moveObj.css({
			top: e.pageY - this.moveCorrectY,
			left: left
		});
		
		var currentPosition = -1;
		for(var i=0; i<this.moveColumnsX.length; i++)
		{
			if(left < this.moveColumnsX[i])
			{
				if(i == this.moveCurrentIndex) break;
					
				var curTd = this.moveTds.eq(i);
				if(curTd.hasClass('kda-ie-moved-to')) return;
				var otherTds = this.moveTds.not(':eq('+i+')');
				otherTds.removeClass('kda-ie-moved-to');
				curTd.addClass('kda-ie-moved-to');
				currentPosition = i;
				break;
			}
		}
		
		if(currentPosition < 0)
		{
			this.moveNewIndex = -1;
			this.moveTds.removeClass('kda-ie-moved-to');
		}
		else
		{
			this.moveNewIndex = currentPosition;
		}
		
		e.stopPropagation();
		return false;
	},
	
	ColumnMoveEnd: function()
	{
		if(!document.getElementById('kda-ie-move-column-abs')) return;
		$('#kda-ie-move-column-abs').remove();
		if(this.moveNewIndex >= 0)
		{
			this.moveTds.removeClass('kda-ie-moved-to');
			
			var td1 = this.moveTds.eq(this.moveCurrentIndex);
			var td2 = this.moveTds.eq(this.moveNewIndex);
			var content1 = $('>div', td1);
			var content2 = $('>div', td2);
			content1.appendTo(td2);
			content2.appendTo(td1);	
			
			var tr = td1.closest('tr');
			this.OnAfterColumnsMove(tr);
		}
	},
	
	ColumnsMoveLeft: function(btn)
	{
		var obj = this;
		var parent = $(btn).closest('.kda-ie-field-select');
		var tr = parent.closest('tr');
		var tds = tr.find('.kda-ie-field-select');
		var find = false;
		var content1 = false;
		for(var i=0; i<tds.length; i++)
		{
			if(parent[0]==tds[i])
			{
				find = true;
				var content1 = $('>div', tds[(i > 0 ? i-1 : i)]);
			}
			if(find && i > 0)
			{
				$('>div', tds[i]).appendTo(tds[i-1]);
			}
		}
		if(content1) content1.appendTo(tds[tds.length - 1]);
		this.ClearColumnField(tds[tds.length - 1]);
		this.OnAfterColumnsMove(tr);
	},
	
	ColumnsMoveRight: function(btn)
	{
		var obj = this;
		var parent = $(btn).closest('.kda-ie-field-select');
		var tr = parent.closest('tr');
		var tds = tr.find('.kda-ie-field-select');
		var content1 = $('>div', tds[tds.length-1]);
		for(var i=tds.length-1; i>=0; i--)
		{
			if(i < tds.length-1) 
			{
				$('>div', tds[i]).appendTo(tds[i+1]);
			}
			if(parent[0]==tds[i])
			{
				break;
			}
		}
		content1.appendTo(tds[i]);
		this.ClearColumnField(tds[i]);
		this.OnAfterColumnsMove(tr);
	},
	
	ClearColumnField: function(td)
	{
		$('>div:not(.kda-ie-field-select-btns):gt(0)', td).remove();
		$('>div:not(.kda-ie-field-select-btns)', td).each(function(){
			//$('input[name^="SETTINGS[FIELDS_LIST]"]', this).val('');
			//$('input[name^="FIELDS_LIST_SHOW"]', this).val('').attr('title', '');
			EList.SetHiddenFieldVal($('input[name^="SETTINGS[FIELDS_LIST]"]', this), '');
			EList.SetShowFieldVal($('span.fieldval', this), '');
			$('.field_settings', this).addClass('inactive');
			$('.field_settings input[name^="EXTRASETTINGS"]', this).val('');
		});
	},
	
	OnAfterColumnsMove: function(tr)
	{
		tr.find('.kda-ie-field-select').each(function(index){
			$(this).find('input').each(function(){
				if(this.name.substr(this.name.length - 1)==']')
				{
					this.name = this.name.replace(/\[\d+((_\d+)?)\]$/, '['+index+'$1]');
				}
			});
			$(this).find('span.fieldval').each(function(){
				this.id = this.id.replace(/(field\-list\-show\-\d+\-)\d+((_\d+)?)/, '$1'+index+'$2');
			});
			$(this).find('.field_settings').each(function(){
				this.id = this.id.replace(/(field_settings_\d+_)\d+((_\d+)?)/, '$1'+index+'$2');
			});
		});
		this.SetFieldAutoInsert(tr);
	},
	
	SetExtraParams: function(oid, returnJson)
	{
		if(typeof returnJson == 'object') returnJson = JSON.stringify(returnJson);
		if(returnJson.length > 0) $('#'+oid).removeClass("inactive");
		else $('#'+oid).addClass("inactive");
		$('#'+oid+' input').val(returnJson);
		if(BX.WindowManager.Get()) BX.WindowManager.Get().Close();
	},
	
	ToggleAddSettings: function(input)
	{
		var display = (input.checked ? '' : 'none');
		var tr = $(input).closest('tr');
		var next;
		while((next = tr.next('tr.subfield')) && next.length > 0)
		{
			tr = next;
			if(display=='' && $('select', tr).length > 0 && $('select option', tr).length==0) continue;
			tr.css('display', display);
		}
	},
	
	ToggleAddSettingsBlock: function(link)
	{
		if($(link).hasClass('open'))
		{
			$(link).removeClass('open');
			$(link).next('div').slideUp();
		}
		else
		{
			$(link).addClass('open');
			$(link).next('div').slideDown();
		}
	}
}

var EProfile = {
	Init: function()
	{
		var select = $('select#PROFILE_ID');
		if(select.length > 0)
		{
			if(typeof select.chosen == 'function')
			{
				setTimeout(function(){$('select#PROFILE_ID').chosen({search_contains: true})}, 500);
			}
			if(select.val().length > 0)
			{
				$.post(window.location.href, {'MODE': 'AJAX', 'ACTION': 'DELETE_TMP_DIRS'}, function(data){});
			}
		
			select = select[0]
			/*this.Choose(select[0]);*/
			$('#new_profile_name').css('display', (select.value=='new' ? '' : 'none'));
		
			var obj = this;
			$('select.adm-detail-iblock-list').bind('change', function(){
				$.post(window.location.href, {'MODE': 'AJAX', 'IBLOCK_ID': this.value, 'ACTION': 'GET_UID'}, function(data){
					var fields = $(data).find('select[name="fields[]"]');
					var select = $('select[name="SETTINGS_DEFAULT[ELEMENT_UID][]"]');
					var modeBtn = select.nextAll('.kda-ie-select-view-mode');
					var mode = modeBtn.attr('mode');
					if(mode=='chosen') modeBtn.trigger('click');
					obj.SetNewUid(select, fields);
					fields.attr('name', select.attr('name'));
					select.replaceWith(fields);
					if(mode=='chosen') modeBtn.trigger('click');
					
					var fields2 = $(data).find('select[name="fields_sku[]"]');
					var select2 = $('select[name="SETTINGS_DEFAULT[ELEMENT_UID_SKU][]"]');
					var modeBtn2 = select2.nextAll('.kda-ie-select-view-mode');
					var mode2 = modeBtn2.attr('mode');
					if(mode2=='chosen') modeBtn2.trigger('click');
					obj.SetNewUid(select2, fields2);
					fields2.attr('name', select2.attr('name'));
					select2.replaceWith(fields2);
					if(mode2=='chosen') modeBtn2.trigger('click');
					if(fields2.length > 0 && fields2[0].options.length > 0)
					{
						$('#element_uid_sku').show();
						$('.kda-sku-block.heading').show();
					}
					else
					{
						$('#element_uid_sku').hide();
						$('.kda-sku-block').hide();
						$('.kda-sku-block.heading .kda-head-more').removeClass('show');
					}
					
					$('#properties_for_sum').replaceWith($(data).find('#properties_for_sum'));
					$('#properties_for_sum_sku').replaceWith($(data).find('#properties_for_sum_sku'));
					var fields = $(data).find('select[name="properties[]"]');
					var select = $('select[name="SETTINGS_DEFAULT[ELEMENT_PROPERTIES_REMOVE][]"]');
					fields.val(select.val());
					fields.attr('name', select.attr('name'));
					if(typeof $('select.kda-chosen-multi').chosen == 'function')
					{
						$('select.kda-chosen-multi').chosen('destroy');
					}
					select.replaceWith(fields);
					if(typeof $('select.kda-chosen-multi').chosen == 'function')
					{
						$('select.kda-chosen-multi').chosen({width: '300px'});
					}
				});
			});
			
			var select = $('select[name="SETTINGS_DEFAULT[ELEMENT_UID][]"]');
			if(select.length > 0 && !select.val()) select[0].options[0].selected = true;
			if(typeof $('select.kda-chosen-multi').chosen == 'function')
			{
				$('select.kda-chosen-multi').chosen({width: '300px'});
				this.AddSelectViewModeBtn(select);
				var select2 = $('select[name="SETTINGS_DEFAULT[ELEMENT_UID_SKU][]"]');
				this.AddSelectViewModeBtn(select2);
			}
			this.ToggleAdditionalSettings();
			
			$('#dataload input[type="checkbox"][data-confirm]').bind('change', function(){
				if(this.checked && !confirm(this.getAttribute('data-confirm')))
				{
					this.checked = false;
				}
			});
		}
	},
	
	SetNewUid: function(oldSelect, newSelect)
	{
		var i, j, option, find,
			oldOptions = $('option', oldSelect),
			newOptions = $('option', newSelect);
		for(i=0; i<oldOptions.length; i++)
		{
			option = oldOptions[i];
			if(!option.selected) continue;
			find = false;
			j = 0;
			while(!find && j<newOptions.length)
			{
				if(option.value==newOptions[j].value)
				{
					newOptions[j].selected = true;
					find = true;
				}
				j++;
			}
			j = 0;
			while(!find && j<newOptions.length)
			{
				if(option.text==newOptions[j].text)
				{
					newOptions[j].selected = true;
					find = true;
				}
				j++;
			}
		}
		
	},
	
	AddSelectViewModeBtn: function(select)
	{
		if(select.nextAll('.kda-ie-select-view-mode').length == 0)
		{
			select.after('<a href="javascript:void(0)" onclick="EProfile.ChangeSelectViewMode(this)" class="kda-ie-select-view-mode" title="'+BX.message("KDA_IE_SELECT_FAST_VIEW")+'"></a>');
			var minput = select.prevAll('input[type="hidden"][name*="SHOW_MODE_"]');
			if(minput.val()=='chosen') setTimeout(function(){select.nextAll('.kda-ie-select-view-mode').trigger('click');}, 200);
		}
	},
	
	ChangeSelectViewMode: function(a)
	{
		var select = $(a).parent().find('select:eq(0)');
		if(select.length > 0 && typeof select.chosen == 'function')
		{
			var minput = select.prevAll('input[type="hidden"][name*="SHOW_MODE_"]');
			if($(a).attr('mode')!='chosen')
			{
				select.chosen({search_contains: true, placeholder_text: BX.message("KDA_IE_SELECT_NOT_CHOSEN")});
				$(a).attr('title', BX.message("KDA_IE_SELECT_STANDARD_VIEW"));
				$(a).attr('mode', 'chosen');
				minput.val('chosen');
			}
			else
			{
				select.chosen('destroy');
				$(a).attr('title', BX.message("KDA_IE_SELECT_FAST_VIEW"));
				$(a).attr('mode', '');
				minput.val('');
			}
		}
	},
	
	Choose: function(select)
	{
		/*if(select.value=='new')
		{
			$('#new_profile_name').css('display', '');
		}
		else
		{
			$('#new_profile_name').css('display', 'none');
		}*/
		var id = (typeof select == 'object' ? select.value : select);
		var query = window.location.search.replace(/PROFILE_ID=[^&]*&?/, '');
		if(query.length < 2) query = '?';
		if(query.length > 1 && query.substr(query.length-1)!='&') query += '&';
		query += 'PROFILE_ID=' + id;
		window.location.href = query;
	},
	
	Delete: function()
	{
		var obj = this;
		var select = $('select#PROFILE_ID');
		var option = select[0].options[select[0].selectedIndex];
		var id = option.value;
		$.post(window.location.href, {'MODE': 'AJAX', 'ID': id, 'ACTION': 'DELETE_PROFILE'}, function(data){
			obj.Choose('');
		});
	},
	
	Copy: function()
	{
		var obj = this;
		var select = $('select#PROFILE_ID');
		var option = select[0].options[select[0].selectedIndex];
		var id = option.value;
		$.post(window.location.href, {'MODE': 'AJAX', 'ID': id, 'ACTION': 'COPY_PROFILE'}, function(data){
			eval('var res = '+data+';');
			obj.Choose(res.id);
		});
	},
	
	ShowRename: function()
	{
		var select = $('select#PROFILE_ID');
		var option = select[0].options[select[0].selectedIndex];
		var name = option.innerHTML;
		
		var tr = $('#new_profile_name');
		var input = $('input[type=text]', tr);
		input.val(name);
		if(!input.attr('init_btn'))
		{
			input.after('&nbsp;<input type="button" onclick="EProfile.Rename();" value="OK">');
			input.attr('init_btn', 1);
		}
		tr.css('display', '');
	},
	
	Rename: function()
	{
		var select = $('select#PROFILE_ID');
		var option = select[0].options[select[0].selectedIndex];
		var id = option.value;
		
		var tr = $('#new_profile_name');
		var input = $('input[type=text]', tr);
		var value = $.trim(input.val());
		if(value.length==0) return false;
		
		tr.css('display', 'none');
		option.innerHTML = value;
		if(typeof select.chosen == 'function')
		{
			$('select#PROFILE_ID').trigger("chosen:updated");;
		}
		
		$.post(window.location.href, {'MODE': 'AJAX', 'ID': id, 'NAME': value, 'ACTION': 'RENAME_PROFILE'}, function(data){});
	},
	
	ShowCron: function()
	{
		var dialog = new BX.CAdminDialog({
			'title':BX.message("KDA_IE_POPUP_CRON_TITLE"),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_cron_settings.php',
			'width':'800',
			'height':'350',
			'resizable':true});
			
		dialog.SetButtons([
			dialog.btnCancel/*,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					this.disableUntilError();
					this.parentWindow.PostParameters();
					//this.parentWindow.Close();
				}
			})*/
		]);
			
		dialog.Show();
	},
	
	SaveCron: function(btn)
	{
		var form = $(btn).closest('form');
		$.post(form[0].getAttribute('action'), form.serialize()+'&subaction='+btn.name, function(data){
			$('#kda-ie-cron-result').html(data);
		});
	},
	
	ShowMassUploader: function()
	{
		var dialog = new BX.CAdminDialog({
			'title':BX.message("KDA_IE_TOOLS_IMG_LOADER_TITLE"),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_mass_uploader.php',
			'width':'900',
			'height':'450',
			'resizable':true});
			
		this.massUploaderDialog = dialog;
		this.MassUploaderSetButtons();
			
		dialog.Show();
	},
	
	MassUploaderSetButtons: function()
	{
		var dialog = this.massUploaderDialog;
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					this.disableUntilError();
					this.parentWindow.PostParameters();
					//this.parentWindow.Close();
				}
			})
		]);
	},
	
	RemoveProccess: function(link, id)
	{
		var post = {
			'MODE': 'AJAX',
			'PROCCESS_PROFILE_ID': id,
			'ACTION': 'REMOVE_PROCESS_PROFILE'
		};
		
		$.ajax({
			type: "POST",
			url: window.location.href,
			data: post,
			success: function(data){
				var parent = $(link).closest('.kda-proccess-item');
				if(parent.parent().find('.kda-proccess-item').length <= 1)
				{
					parent.closest('.adm-info-message-wrap').hide();
				}
				parent.remove();
			}
		});
	},
	
	ContinueProccess: function(link, id)
	{
		var parent = $(link).closest('div');
		parent.append('<form method="post" action="" style="display: none;">'+
						'<input type="hidden" name="PROFILE_ID" value="'+id+'">'+
						'<input type="hidden" name="STEP" value="3">'+
						'<input type="hidden" name="PROCESS_CONTINUE" value="Y">'+
						'<input type="hidden" name="sessid" value="'+$('#sessid').val()+'">'+
					  '</form>');
		parent.find('form')[0].submit();
	},
	
	ToggleAdditionalSettings: function(link)
	{
		if(link) link = $(link);
		else link = $('.kda-head-more');
		if(link.length==0) return;
		$(link).each(function(){
			var tr = $(this).closest('tr');
			var show = $(this).hasClass('show');
			while((tr = tr.next('tr:not(.heading)')) && tr.length > 0)
			{
				if(show) tr.hide();
				else tr.show();
			}
			if(show) $(this).removeClass('show');
			else $(this).addClass('show');
		});
	},
	
	RadioChb: function(chb1, chb2name, confirmMessage)
	{
		if(chb1.checked)
		{
			if(!confirmMessage || confirm(confirmMessage))
			{
				var form = $(chb1).closest('form');
				if(typeof chb2name=='object')
				{
					for(var i=0; i<chb2name.length; i++)
					{
						if(form[0][chb2name[i]])
						{
							form[0][chb2name[i]].checked = false;
							$(form[0][chb2name[i]]).trigger('change');
						}
					}
				}
				if(form[0][chb2name])
				{
					form[0][chb2name].checked = false;
					$(form[0][chb2name]).trigger('change');
				}
			}
			else
			{
				chb1.checked = false;
			}
		}
	},
	
	OpenMissignElementFields: function(link)
	{
		var form = $(link).closest('form');
		var iblockId = $('select[name="SETTINGS_DEFAULT[IBLOCK_ID]"]', form).val();
		var input = $(link).prev('input[type=hidden]');
		
		var dialogParams = {
			'title':BX.message(input.attr('id').indexOf('OFFER_')==0 ? "KDA_IE_POPUP_MISSINGOFFER_FIELDS_TITLE" : "KDA_IE_POPUP_MISSINGELEM_FIELDS_TITLE"),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_missignelem_fields.php?IBLOCK_ID='+iblockId+'&INPUT_ID='+input.attr('id'),
			'content_post': {OLDDEFAULTS: input.val()},
			'width':'800',
			'height':'400',
			'resizable':true
		};
		var dialog = new BX.CAdminDialog(dialogParams);
			
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					this.disableUntilError();
					this.parentWindow.PostParameters();
					//this.parentWindow.Close();
				}
			})
		]);
			
		BX.addCustomEvent(dialog, 'onWindowRegister', function(){
			$('input[type=checkbox]', this.DIV).each(function(){
				BX.adminFormTools.modifyCheckbox(this);
			});
			$('select.kda-ie-chosen-multi').chosen();
		});
			
		dialog.Show();
		
		return false;
	},
	
	OpenMissignElementFilter: function(link)
	{
		var obj = this;
		var form = $(link).closest('form');
		var iblockId = $('select[name="SETTINGS_DEFAULT[IBLOCK_ID]"]', form).val();
		
		var dialogParams = {
			'title':BX.message("KDA_IE_POPUP_MISSINGELEM_FILTER_TITLE"),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_missignelem_filter.php?IBLOCK_ID='+iblockId+'&PROFILE_ID='+$('#PROFILE_ID').val(),
			'content_post': {OLDFILTER: $('#CELEMENT_MISSING_FILTER').val()},
			'width':'800',
			'height':'400',
			'resizable':true
		};
		var dialog = new BX.CAdminDialog(dialogParams);
			
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					$('#kda-ie-filter').find('tr[id*="_filter_row_"]:hidden').find('input,select,textarea').val('').trigger('change');
					$.post('/bitrix/admin/'+kdaIEModuleFilePrefix+'_missignelem_filter.php', $('#kda-ie-filter').serialize(), function(data){
						$('#CELEMENT_MISSING_FILTER').val($.trim(data));
						BX.WindowManager.Get().Close();
					});
				}
			})
		]);
		
		BX.addCustomEvent(dialog, 'onWindowRegister', function(){
			setTimeout(function(){
				$('.find_form_inner select[name*="find_el_vtype_"]').bind('change', function(){
					var div = $(this.parentNode).next();
					if(this.value.length > 0 && this.value.indexOf('empty')!=-1) div.hide();
					else div.show();
				}).trigger('change');
			}, 500);
		});
			
		dialog.Show();
		
		return false;
	},
	
	ShowEmailForm: function()
	{
		var pid = $('#PROFILE_ID').val();
		var dialog = new BX.CAdminDialog({
			'title':BX.message("KDA_IE_POPUP_SOURCE_EMAIL"),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_source_email.php?PROFILE_ID='+pid,
			'content_post': {EMAIL_SETTINGS: $('.kda-ie-file-choose input[name="SETTINGS_DEFAULT[EMAIL_DATA_FILE]"]').val()},
			'width':'900',
			'height':'450',
			'resizable':true});
			
		BX.addCustomEvent(dialog, 'onWindowRegister', function(){
			
		});
		
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					this.disableUntilError();
					this.parentWindow.PostParameters();
					//this.parentWindow.Close();
				}
			})
		]);
			
		dialog.Show();
	},
	
	CheckEmailConnectData: function(link)
	{
		var form = $(link).closest('form');
		var post = form.serialize()+'&action=checkconnect';
		$.ajax({
			type: "POST",
			url: form.attr('action'),
			data: post,
			success: function(data){
				eval('var res = '+data+';');
				if(res.result=='success') $('#connect_result').html('<div class="success">'+BX.message("KDA_IE_SOURCE_EMAIL_SUCCESS")+'</div>');
				else $('#connect_result').html('<div class="fail">'+BX.message("KDA_IE_SOURCE_EMAIL_FAIL")+'</div>');
				
				if(res.folders)
				{
					var select = $('select[name="EMAIL_SETTINGS[FOLDER]"]', form);
					var oldVal = select.val();
					$('option', select).remove();
					for(var i in res.folders)
					{
						var option = $('<option>'+res.folders[i]+'</option>');
						option.attr('value', i);
						select.append(option);
					}
					select.val(oldVal);
				}
			},
			error: function(){
				$('#connect_result').html('<div class="fail">'+BX.message("KDA_IE_SOURCE_EMAIL_FAIL")+'</div>');
			},
			timeout: 15000
		});
	},
	
	ShowFileAuthForm: function()
	{
		var pid = $('#PROFILE_ID').val();
		var post = '';
		var json = $('.kda-ie-file-choose input[name="EXT_DATA_FILE"]').val();
		if(json && json.substr(0,1)=='{')
		{
			//eval('post = {AUTH_SETTINGS: '+json+'};');
			post = {AUTH_SETTINGS: json};
		}
		var dialog = new BX.CAdminDialog({
			'title':BX.message("KDA_IE_POPUP_SOURCE_LINKAUTH"),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_source_linkauth.php?PROFILE_ID='+pid,
			'content_post': post,
			'width':'900',
			'height':'450',
			'resizable':true});
			
		BX.addCustomEvent(dialog, 'onWindowRegister', function(){
			
		});
		
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					this.disableUntilError();
					this.parentWindow.PostParameters();
					//this.parentWindow.Close();
				}
			})
		]);
			
		dialog.Show();
	},
	
	SetLinkAuthParams: function(jData)
	{
		if($('.kda-ie-file-choose input[name="EXT_DATA_FILE"]').length == 0)
		{
			$(".kda-ie-file-choose").prepend('<input type="hidden" name="EXT_DATA_FILE" value="">');
		}
		$('.kda-ie-file-choose input[name="EXT_DATA_FILE"]').val(JSON.stringify(jData));
		$('.kda-ie-file-choose input[name="SETTINGS_DEFAULT[EMAIL_DATA_FILE]"]').val('');
		BX.WindowManager.Get().Close();
	},
	
	LauthAddVar: function(link)
	{
		var tr = $(link).closest('tr').prev('tr.kda-ie-lauth-var');
		var newTr = tr.clone();
		newTr.find('input').val('');
		tr.after(newTr);
	},
	
	CheckLauthConnectData: function(link)
	{
		var form = $(link).closest('form');
		var post = form.serialize()+'&action=checkconnect';
		$.ajax({
			type: "POST",
			url: form.attr('action'),
			data: post,
			success: function(data){
				eval('var res = '+data+';');
				if(res.result=='success') $('#connect_result').html('<div class="success">'+BX.message("KDA_IE_SOURCE_LAUTH_SUCCESS")+'</div>');
				else $('#connect_result').html('<div class="fail">'+BX.message("KDA_IE_SOURCE_LAUTH_FAIL")+'</div>');
			},
			error: function(){
				$('#connect_result').html('<div class="fail">'+BX.message("KDA_IE_SOURCE_LAUTH_FAIL")+'</div>');
			},
			timeout: 30000
		});
	},
	
	LauthLoadParams: function(link)
	{
		var form = $(link).closest('form');
		var post = form.serialize()+'&action=loadparams';
		$.ajax({
			type: "POST",
			url: form.attr('action'),
			data: post,
			success: function(data){
				if(data.length==0) return;
				eval('var res = '+data+';');
				if(typeof res!='object') return;
				
				var varInputs = $('input[name="vars[]"]', form);
				var emptyVals = true;
				for(var i=0; i<varInputs.length; i++)
				{
					if($.trim($(varInputs[i]).val()).length > 0) emptyVals = false;
				}
				if(emptyVals && typeof res.VARS=='object')
				{
					var countVars = varInputs.length;
					while(countVars < res.VARS.length)
					{
						$('td.kda-ie-lauth-addvar a', form).trigger('click');
						countVars++;
					}
					varInputs = $('input[name="vars[]"]', form);
					for(var i=0; i<varInputs.length; i++)
					{
						if(res.VARS[i]) $(varInputs[i]).val(res.VARS[i]);
					}
				}
				var postAuthInput = $('input[name="AUTH_SETTINGS[POSTPAGEAUTH]"]', form);
				if($.trim(postAuthInput.val()).length == 0 && res.LOC)
				{
					postAuthInput.val(res.LOC);
				}
			},
			timeout: 8000
		});
	},
	
	OpenCalcPriceForm: function(link)
	{
		var form = $(link).closest('form');
		var iblockId = $('select[name="SETTINGS_DEFAULT[IBLOCK_ID]"]', form).val();
		
		var dialogParams = {
			'title':BX.message("KDA_IE_POPUP_CALULATE_PRICE_TITLE"),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_price_calculating.php?IBLOCK_ID='+iblockId,
			'width':'800',
			'height':'400',
			'resizable':true
		};
		var dialog = new BX.CAdminDialog(dialogParams);
			
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('JS_CORE_WINDOW_SAVE'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					this.disableUntilError();
					this.parentWindow.PostParameters();
					//this.parentWindow.Close();
				}
			})
		]);
			
		BX.addCustomEvent(dialog, 'onWindowRegister', function(){
			$('input[type=checkbox]', this.DIV).each(function(){
				BX.adminFormTools.modifyCheckbox(this);
			});
			$('select.kda-ie-chosen-multi').chosen();
		});
			
		dialog.Show();
		
		return false;
	},
	
	RelTablePriceRowAdd: function(link)
	{
		var tbl = $(link).prev('table');
		var index = 0;
		while($('tr[data-index="'+index+'"]', tbl).length > 0) index++;
		var tr = $('tr:last', tbl).clone();
		tr.attr('data-index', index);
		$('input', tr).remove();
		$('a', tr).each(function(){this.innerHTML = this.getAttribute('data-default-text');});
		tr.appendTo(tbl);
	},
	
	RelTablePriceRowRemove: function(link)
	{
		var tr = $(link).closest('tr');
		var tbl = tr.closest('table');
		if($('tr', tbl).length > 2) tr.remove();
		else
		{
			$('input', tr).remove();
			$('a', tr).each(function(){this.innerHTML = this.getAttribute('data-default-text');});
		}
	},
	
	RelTablePriceShowSelect: function(link, fname)
	{
		var iblockId = $(link).closest('table').attr('data-iblock-id');
		var parentDiv = $(link).closest('.kda-ie-select-mapping');
		var index = $(link).closest('tr').attr('data-index');
		var parentForm = $(link).closest('form');
		var selectObj = parentForm.find('select[name="'+fname+'"]').clone();
		selectObj.val($('input:first', parentDiv).val());
		parentDiv.append(selectObj);
		selectObj.bind('change', function(){
			var selectedOption = this.options.item(this.selectedIndex);
			var fieldName = '';
			var optgroup = $(selectedOption).closest('optgroup');
			if(optgroup.length > 0)
			{
				fieldName = optgroup.attr('label');
				if(fieldName.length > 0) fieldName += ' - ';
			}
			fieldName += selectedOption.text
			link.innerHTML = fieldName;
			$('input[name^="MAP['+iblockId+']["]', parentDiv).remove();
			if(this.value.length > 0)
			{
				parentDiv.prepend('<input type="hidden" name="MAP['+iblockId+']['+index+']['+fname+']" value="">');
				$('input[name="MAP['+iblockId+']['+index+']['+fname+']"]', parentDiv).val(this.value);
			}
			if(typeof selectObj.chosen == 'function') selectObj.chosen('destroy');
			$(this).remove();
			$(link).show();
		});
		if(typeof selectObj.chosen == 'function') selectObj.chosen({search_contains: true});
		$(link).hide();
		
		if(selectObj.next('.chosen-container').length > 0)
		{
			$('body').one('click', function(e){
				e.stopPropagation();
				return false;
			});
			var chosenDiv = selectObj.next('.chosen-container')[0];
			$('a:eq(0)', chosenDiv).trigger('mousedown');
			
			var lastClassName = chosenDiv.className;
			var interval = setInterval( function() {   
				   var className = chosenDiv.className;
					if (className !== lastClassName) {
						selectObj.trigger('change');
						lastClassName = className;
						clearInterval(interval);
					}
				},50);
		}
	}
}

var EProfileList = {
	ShowRestoreWindow: function()
	{
		var windowUrl = '/bitrix/admin/'+kdaIEModuleFilePrefix+'_restore_profiles.php?lang='+BX.message('LANGUAGE_ID');
		var dialogParams = {
			'title':BX.message("KDA_IE_POPUP_RESTORE_PROFILES_TITLE"),
			'content_url':windowUrl,
			'width':'700',
			'height':'300',
			'resizable':true
		};
		var dialog = new BX.CAdminDialog(dialogParams);
		this.restoreDialog = dialog;
		this.RestoreDialogButtonsSet();		
			
		BX.addCustomEvent(dialog, 'onWindowRegister', function(){
			$('input[type=checkbox]', this.DIV).each(function(){
				BX.adminFormTools.modifyCheckbox(this);
			});
			
			var newForm = $('#restore_profiles');
			$('input[type=file]', newForm).bind('change', function(){
				var listWrapRow = $('#kda_restore_profile_list_row');
				var listWrap = $('#kda_restore_profile_list');
				listWrapRow.hide();
				listWrap.html('');
				if(!this.value) return;
				$.ajax({
					url: windowUrl+'&action=getprofilesfromfile',
					type: 'POST',
					data: (new FormData(newForm[0])),
					mimeType:"multipart/form-data",
					contentType: false,
					cache: false,
					processData:false,
					success: function(data, textStatus, jqXHR)
					{
						eval('var res='+data);
						if((typeof res!='object') || res.TYPE!='SUCCESS' || (typeof res.PROFILES!='object')) return;
						listWrapRow.show();
						listWrap.append('<div><input type="checkbox" name="PARAMS[IDS][]" value="ALL" id="kda_ie_restoreprofile_all" checked> <label for="kda_ie_restoreprofile_all">'+BX.message('KDA_IE_POPUP_RESTORE_PROFILES_ALL')+'</label></div>');
						for(var i=0; i<res.PROFILES.length; i++)
						{
							listWrap.append('<div style="padding-left: 15px;"><input type="checkbox" name="PARAMS[IDS][]" value="'+res.PROFILES[i].ID+'" id="kda_ie_restoreprofile_'+res.PROFILES[i].ID+'" checked> <label for="kda_ie_restoreprofile_'+res.PROFILES[i].ID+'">'+res.PROFILES[i].NAME+'</label></div>');
						}
						$('#kda_ie_restoreprofile_all').bind('change', function(){
							var id = this.id;
							var checked = this.checked;
							$(this).closest('#kda_restore_profile_list').find('input[type="checkbox"]').each(function(){
								if(!this.id || this.id!=id) this.checked = checked;
							});
						});
					}
				});
			});
		});
			
		dialog.Show();
	},
	
	RestoreDialogButtonsSet: function(fireEvents)
	{
		var dialog = this.restoreDialog;
		dialog.SetButtons([
			dialog.btnCancel,
			new BX.CWindowButton(
			{
				title: BX.message('KDA_IE_POPUP_RESTORE_PROFILES_SAVE_BTN'),
				id: 'savebtn',
				name: 'savebtn',
				className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
				action: function () {
					var btn = this;
					btn.disable();
					
					$.ajax({
						url: '/bitrix/admin/'+kdaIEModuleFilePrefix+'_restore_profiles.php',
						type: 'POST',
						data: (new FormData(document.getElementById('restore_profiles'))),
						mimeType:"multipart/form-data",
						contentType: false,
						cache: false,
						processData:false,
						success: function(data, textStatus, jqXHR)
						{
							if(data && data.substr(0, 1)=='{' && data.substr(data.length-1)=='}')
							{
								eval('var result = '+data+';');
							}
							else
							{
								var result = false;
							}
							
							if(typeof result == 'object')
							{
								if(result.MESSAGE) alert(result.MESSAGE);
								if(result.TYPE=='SUCCESS')
								{
									setTimeout(function(){
										window.location.href = window.location.href;
									}, 3000);
								}
							}
							btn.enable();
						},
						error: function(data, textStatus, jqXHR)
						{
							btn.enable();
						}
					});
				}
			})
		]);
		
		if(fireEvents)
		{
			BX.onCustomEvent(dialog, 'onWindowRegister');
		}
	}
}

var EImport = {
	params: {},

	Init: function(post, params)
	{
		BX.scrollToNode($('#resblock .adm-info-message')[0]);
		this.wait = BX.showWait();
		this.post = post;
		if(typeof params == 'object') this.params = params;
		this.SendData();
		this.pid = post.PROFILE_ID;
		this.idleCounter = 0;
		this.errorStatus = false;
		var obj = this;
		setTimeout(function(){obj.SetTimeout();}, 3000);
		obj.UpdateTime();
	},
	
	UpdateTime: function()
	{
		if($('#progressbar').hasClass('end') || !document.getElementById('execution_time')) return;
		var timeBegin = parseInt($('#execution_time').attr('data-time-begin'));
		if(!timeBegin)
		{
			timeBegin = (new Date()).getTime();
			$('#execution_time').attr('data-time-begin', timeBegin);
		}
		var days = 0, hours = 0, minutes = 0, seconds = 0;
		var seconds = Math.round(((new Date()).getTime() - timeBegin) / 1000);
		if(seconds >= 60){minutes = Math.floor(seconds/60); seconds = seconds%60;}
		if(minutes >= 60){hours = Math.floor(minutes/60); minutes = minutes%60;}
		if(hours >= 24){days = Math.floor(hours/60); hours = hours%60;}
		$('#execution_time').html((days > 0 ? days+' '+BX.message("KDA_IE_TIME_DAYS")+' ' : '')+(hours > 0 ? hours+' '+BX.message("KDA_IE_TIME_HOURS")+' ' : '')+(minutes > 0 ? minutes+' '+BX.message("KDA_IE_TIME_MINUTES")+' ' : '')+seconds+' '+BX.message("KDA_IE_TIME_SECONDS"));
		var obj = this;
		setTimeout(function(){obj.UpdateTime();}, 1000);
	},
	
	SetTimeout: function()
	{
		if($('#progressbar').hasClass('end')) return;
		var obj = this;
		if(this.timer) clearTimeout(this.timer);
		this.timer = setTimeout(function(){obj.GetStatus();}, 2000);
	},
	
	GetStatus: function()
	{
		var obj = this;
		$.ajax({
			type: "GET",
			url: '/upload/tmp/'+kdaIEModuleName+'/'+kdaIEModuleAddPath+this.pid+'.txt?hash='+(new Date()).getTime(),
			success: function(data){
				var finish = false;
				if(data && data.substr(0, 1)=='{' && data.substr(data.length-1)=='}')
				{
					try {
						eval('var result = '+data+';');
					} catch (err) {
						var result = false;
					}
				}
				else
				{
					var result = false;
				}
				
				if(typeof result == 'object')
				{
					if(result.action!='finish')
					{
						obj.UpdateStatus(result);
					}
					else
					{
						obj.UpdateStatus(result, true);
						var finish = true;
					}
				}
				if(!finish) obj.SetTimeout();
			},
			error: function(){
				obj.SetTimeout();
			},
			timeout: 5000
		});
	},
	
	UpdateStatus: function(result, end)
	{
		if($('#progressbar').hasClass('end')) return;
		if(end && this.timer) clearTimeout(this.timer);
		
		if(typeof result == 'object')
		{
			if(end && (parseInt(result.total_read_line) < parseInt(result.total_file_line)))
			{
				result.total_read_line = result.total_file_line;
			}
			
			var paramTag;
			for(var i in result)
			{
				if(!i.match(/^[A-Za-z0-9_]+$/)) continue;
				paramTag = $('#kda_ie_result_wrap #'+i);
				if(paramTag.length==0) continue;
				paramTag.html(result[i]);
				if(result[i] > 0) paramTag.closest('span').addClass('kda-ie-result-item-full');
			}
			
			var span = $('#progressbar .presult span');
			//span.html(span.attr('data-prefix')+': '+result.total_read_line+'/'+result.total_file_line);
			//span.css('visibility', 'hidden');
			if(result.curstep && span.attr('data-'+result.curstep))
			{
				span.html(span.attr('data-'+result.curstep));
			}
			if(end)
			{
				span.css('visibility', 'hidden');
				$('#progressbar .presult').removeClass('load');
				$('#progressbar').addClass('end');
			}
			var percent = Math.round((result.total_read_line / result.total_file_line) * 100);
			if(percent >= 100)
			{
				if(end) percent = 100;
				else percent = 99;
			}
			$('#progressbar .presult b').html(percent+'%');
			$('#progressbar .pline').css('width', percent+'%');
			
			var statLink = document.getElementById('kda_ie_stat_profile_link');
			if(statLink && result.loggerExecId)
			{
				statLink.href = statLink.href.replace(/find_exec_id=(&|$)/, 'find_exec_id='+result.loggerExecId);
			}
			
			if(this.tmpparams && this.tmpparams.total_read_line==result.total_read_line)
			{
				this.idleCounter++;
			}
			else
			{
				this.idleCounter = 0;
			}
			this.tmpparams = result;
		}
		
		/*if(this.idleCounter > 10 && this.errorStatus)
		{
			var obj = this;
			for(var i in obj.tmpparams)
			{
				obj.params[i] = obj.tmpparams[i];
			}
			obj.SendDataSecondary();
		}*/
	},
	
	SendData: function()
	{
		var post = this.post;
		post.ACTION = 'DO_IMPORT';
		post.stepparams = this.params;
		var obj = this;
		
		$.ajax({
			type: "POST",
			url: window.location.href,
			data: post,
			success: function(data){
				obj.errorStatus = false;
				obj.OnLoad(data);
			},
			error: function(data){
				if(data && data.responseText && data.responseText.substr(0, 1)=='{' && data.responseText.substr(data.responseText.length-1)=='}')
				{
					obj.errorStatus = false;
					obj.OnLoad(data.responseText);
					return;
				}
				obj.errorStatus = true;
				$('#block_error_import').show();
				var timeBlock = document.getElementById('kda_ie_auto_continue_time');
				if(timeBlock)
				{
					timeBlock.innerHTML = '';
					obj.TimeoutOnAutoConinue();
				}
			},
			timeout: (post.STEPS_TIME ? ((Math.min(3600, post.STEPS_TIME) + 120) * 1000) : 180000)
		});
	},
	
	TimeoutOnAutoConinue: function()
	{
		var obj = this;
		var timeBlock = document.getElementById('kda_ie_auto_continue_time');
		var time = timeBlock.innerHTML;
		if(time.length==0)
		{
			timeBlock.innerHTML = 30;
		}
		else
		{
			time = parseInt(time) - 1;
			timeBlock.innerHTML = time;
			if(time < 1)
			{
				//$('#kda_ie_continue_link').trigger('click');

				$.ajax({
					type: "POST",
					url: window.location.href,
					data: {'MODE': 'AJAX', 'PROCCESS_PROFILE_ID': obj.pid, 'ACTION': 'GET_PROCESS_PARAMS'},
					success: function(data){
						if(data && data.substr(0, 1)=='{' && data.substr(data.length-1)=='}')
						{
							try {
								eval('var params = '+data+';');
							} catch (err) {
								var params = false;
							}
							if(typeof params == 'object')
							{
								obj.params = params;
							}
						}
						$('#block_error_import').hide();
						obj.errorStatus = false;
						obj.SendDataSecondary();
					},
					error: function(){
						timeBlock.innerHTML = '';
						obj.TimeoutOnAutoConinue();
					}
				});
				return;
			}
		}
		setTimeout(function(){obj.TimeoutOnAutoConinue();}, 1000);
	},
	
	SendDataSecondary: function(woDelay)
	{
		var obj = this;
		if(this.post.STEPS_DELAY && !woDelay)
		{
			setTimeout(function(){
				obj.SendData();
			}, parseInt(this.post.STEPS_DELAY) * 1000);
		}
		else
		{
			obj.SendData();
		}
	},
	
	OnLoad: function(data)
	{
		data = $.trim(data);
		var returnLabel = '<!--module_return_data-->';
		if(data.indexOf(returnLabel)!=-1)
		{
			data = $.trim(data.substr(data.indexOf(returnLabel) + returnLabel.length));
		}
		if(data.indexOf('{')!=0)
		{
			if(data.indexOf("'bitrix_sessid':'")!=-1)
			{
				var sessid = data.substr(data.indexOf("'bitrix_sessid':'") + 17);
				sessid = sessid.substr(0, sessid.indexOf("'"));
				if(sessid.length > 0) this.post.sessid = sessid;
			}
			else if(data.indexOf(".settings.php")!=-1 || data.indexOf("[Error]")!=-1 || data.indexOf("MySQL Query Error")!=-1)
			{
				$('#block_error').show();
				$('#res_error').append('<div>'+data+'</div>');
			}
			var obj = this;
			setTimeout(function(){obj.SendDataSecondary();}, 5000);
			return true;
		}
		try {
			eval('var result = '+data+';');
		} catch (err) {
			var result = false;
		}
		if(typeof result == 'object')
		{
			if(result.sessid)
			{
				$('#sessid').val(result.sessid);
				this.post.sessid = result.sessid;
			}
			
			if(typeof result.errors == 'object' && result.errors.length > 0)
			{
				$('#block_error').show();
				for(var i=0; i<result.errors.length; i++)
				{
					$('#res_error').append('<div>'+result.errors[i]+'</div>');
				}
			}
			
			if(result.action=='continue')
			{
				this.UpdateStatus(result.params);
				this.params = result.params;
				this.SendDataSecondary();
				return true;
			}
		}
		else
		{
			this.SendDataSecondary();
			return true;
		}

		if(result.action=='finish')
		{
			this.UpdateStatus(result.params, true);
			this.params = result.params;
			this.params.finishstatus = 'Y';
			this.SendDataSecondary(true);
			return true;
		}
		
		BX.closeWait(null, this.wait);
		/*$('#res_continue').hide();
		$('#res_finish').show();*/
		
		if(typeof result == 'object' && result.params.redirect_url && result.params.redirect_url.length > 0)
		{
			$('#redirect_message').html($('#redirect_message').html() + result.params.redirect_url);
			$('#redirect_message').show();
			setTimeout(function(){window.location.href = result.params.redirect_url}, 3000);
		}
		return false;
	}
}

var ESettings = {
	AddValue: function(link)
	{
		var div = $(link).prev('div').clone(true);
		$('input, select', div).val('').show();
		$(link).before(div);
	},
	
	OnValChange: function(select)
	{
		var input = $(select).next('input');
		var val = $(select).val();
		if(val.length > 0) input.hide();
		else input.show();
		input.val(val);
	},
	
	AddMargin: function(link)
	{
		var div = $(link).closest('td').find('.kda-ie-settings-margin:eq(0)');
		if(!div.is(':visible'))
		{
			div.show();
		}
		else
		{
			var div2 = div.clone(true);
			$('input', div2).val('');
			$('select', div2).prop('selectedIndex', 0);  
			$(link).before(div2);
		}
	},
	
	RemoveMargin: function(link)
	{
		var divs = $(link).closest('td').find('.kda-ie-settings-margin');
		if(divs.length > 1)
		{
			$(link).closest('.kda-ie-settings-margin').remove();
		}
		else
		{
			$('input', divs).val('');
			$('select', divs).prop('selectedIndex', 0);  
			divs.hide();
		}
	},
	
	ShowMarginTemplateBlock: function(link)
	{
		$('#margin_templates_load').hide();
		var div = $('#margin_templates');
		div.toggle();
	},
	
	ShowMarginTemplateBlockLoad: function(link, action)
	{
		$('#margin_templates').hide();
		var div = $('#margin_templates_load');
		if(action == 'hide') div.hide();
		else div.toggle();
	},
	
	SaveMarginTemplate: function(input, message)
	{
		var div = $(input).closest('div');
		var tid = $('select[name=MARGIN_TEMPLATE_ID]', div).val();
		var tname = $('input[name=MARGIN_TEMPLATE_NAME]', div).val();
		if(tid.length==0 && tname.length==0) return false;
		
		var wm = BX.WindowManager.Get();
		var url = wm.PARAMS.content_url;
		var params = wm.GetParameters().replace(/(^|&)action=[^&]*($|&)/, '&').replace(/^&+/, '').replace(/&+$/, '')
		params += '&action=save_margin_template&template_id='+tid+'&template_name='+tname;
		$.post(url, params, function(data){
			var jData = $(data);
			$('#margin_templates').replaceWith(jData.find('#margin_templates'));
			$('#margin_templates_load').replaceWith(jData.find('#margin_templates_load'));
			alert(message);
		});
		
		return false;
	},
	
	LoadMarginTemplate: function(input)
	{
		var div = $(input).closest('div');
		var tid = $('select[name=MARGIN_TEMPLATE_ID]', div).val();
		if(tid.length==0) return false;
		
		var wm = BX.WindowManager.Get();
		var url = wm.PARAMS.content_url;
		var params = wm.GetParameters().replace(/(^|&)action=[^&]*($|&)/, '&').replace(/^&+/, '').replace(/&+$/, '')
		params += '&action=load_margin_template&template_id='+tid;
		var obj = this;
		$.post(url, params, function(data){
			var jData = $(data);
			$('#settings_margins').replaceWith(jData.find('#settings_margins'));
			obj.ShowMarginTemplateBlockLoad('hide');
		});
		
		return false;
	},
	
	RemoveMarginTemplate: function(input, message)
	{
		var div = $(input).closest('div');
		var tid = $('select[name=MARGIN_TEMPLATE_ID]', div).val();
		if(tid.length==0) return false;
		
		var wm = BX.WindowManager.Get();
		var url = wm.PARAMS.content_url;
		var params = wm.GetParameters().replace(/(^|&)action=[^&]*($|&)/, '&').replace(/^&+/, '').replace(/&+$/, '')
		params += '&action=delete_margin_template&template_id='+tid;
		$.post(url, params, function(data){
			var jData = $(data);
			$('#margin_templates').replaceWith(jData.find('#margin_templates'));
			$('#margin_templates_load').replaceWith(jData.find('#margin_templates_load'));
			alert(message);
		});
		
		return false;
	},
	
	BindConversionEvents: function()
	{
		$('.kda-ie-settings-conversion:not([data-events-init])').each(function(){
			var parent = this;
			$(this).attr('data-events-init', 1);
			$('select.field_cell', parent).bind('change', function(){
				if(this.value=='ELSE' || this.value=='LOADED' || this.value=='DUPLICATE')
				{
					$('select.field_when', parent).hide();
					$('input.field_from', parent).hide();
				}
				else
				{
					$('select.field_when', parent).show();
					$('input.field_from', parent).show();
				}
			}).trigger('change');
		});
	},
	
	AddConversion: function(link, event)
	{
		var prevDiv = $(link).prev('.kda-ie-settings-conversion');
		if(!prevDiv.is(':visible'))
		{
			prevDiv.show();
		}
		else
		{
			var div = prevDiv.clone();
			div.removeAttr('data-events-init');
			if(typeof event == 'object' && (event.ctrlKey || event.shiftKey))
			{
				$('select, input', prevDiv).each(function(){
					$(this.tagName.toLowerCase()+'[name="'+this.name+'"]', div).val(this.value);
				});
			}
			else
			{
				$('input', div).not('.choose_val').val('');
				$('select', div).prop('selectedIndex', 0); 
			}
			$(link).before(div);
		}
		ESettings.BindConversionEvents();
		return false;
	},
	
	RemoveConversion: function(link)
	{
		var div = $(link).closest('.kda-ie-settings-conversion');
		if($(link).closest('td').find('.kda-ie-settings-conversion').length > 1)
		{
			div.remove();
		}
		else
		{
			$('input', div).not('.choose_val').val('');
			$('select', div).prop('selectedIndex', 0); 
			div.hide();
		}
	},
	
	ConversionUp: function(link)
	{
		var div = $(link).closest('.kda-ie-settings-conversion');
		var prev = div.prev('.kda-ie-settings-conversion');
		if(prev.length > 0)
		{
			div.insertBefore(prev);
		}
	},
	
	ConversionDown: function(link)
	{
		var div = $(link).closest('.kda-ie-settings-conversion');
		var next = div.next('.kda-ie-settings-conversion');
		if(next.length > 0)
		{
			div.insertAfter(next);
		}
	},
	
	ShowChooseVal: function(btn, cnt, onlyCells)
	{
		if(cnt < 1) return;
		var field = $(btn).prev('input')[0];
		this.focusField = field;
		var arLines = [];
		
		var colLetters = [];
		for(var k='A'.charCodeAt(0); k<='Z'.charCodeAt(0); k++)
		{
			colLetters.push(String.fromCharCode(k));
		}
		for(var k='A'.charCodeAt(0); k<='Z'.charCodeAt(0); k++)
		{
			for(var k2='A'.charCodeAt(0); k2<='Z'.charCodeAt(0); k2++)
			{
				colLetters.push(String.fromCharCode(k, k2));
			}
		}
		for(var i=0; i<cnt; i++)
		{
			arLines.push({'TEXT':admKDASettingMessages.CELL_VALUE+' '+(i+1)+' ('+colLetters[i]+')','TITLE':'#CELL'+(i+1)+'# - '+admKDASettingMessages.CELL_VALUE+' '+(i+1)+' ('+colLetters[i]+')','ONCLICK':'ESettings.SetUrlVar(\'#CELL'+(i+1)+'#\')'});
		}
		
		if(!onlyCells)
		{
			if(admKDASettingMessages.VALUES && typeof admKDASettingMessages.VALUES=='object')
			{
				var values = admKDASettingMessages.VALUES;
				var menuValsItems = [];
				for(var i=0; i<values.length; i++)
				{
					menuValsItems.push({
						TEXT: values[i],
						TITLE: values[i],
						ONCLICK: 'ESettings.SetUrlVar(this)'
					});
				}
				arLines.push({'TEXT':BX.message("KDA_IE_PROP_VALUES"),MENU: menuValsItems});
			}
			for(var key in admKDASettingMessages)
			{
				if(key.indexOf('RATE_')==0)
				{
					var currency = key.substr(5);
					arLines.push({'TEXT':admKDASettingMessages[key],'TITLE':'#'+currency+'# - '+admKDASettingMessages[key],'ONCLICK':'ESettings.SetUrlVar(\'#'+currency+'#\')'});
				}
			}
			arLines.push({'TEXT':admKDASettingMessages.CELL_LINK,'TITLE':'#CLINK# - '+admKDASettingMessages.CELL_LINK,'ONCLICK':'ESettings.SetUrlVar(\'#CLINK#\')'});
			arLines.push({'TEXT':admKDASettingMessages.CELL_COMMENT,'TITLE':'#CNOTE# - '+admKDASettingMessages.CELL_COMMENT,'ONCLICK':'ESettings.SetUrlVar(\'#CNOTE#\')'});
			arLines.push({'TEXT':admKDASettingMessages.IFILENAME,'TITLE':'#FILENAME# - '+admKDASettingMessages.IFILENAME,'ONCLICK':'ESettings.SetUrlVar(\'#FILENAME#\')'});
			arLines.push({'TEXT':admKDASettingMessages.ISHEETNAME,'TITLE':'#SHEETNAME# - '+admKDASettingMessages.ISHEETNAME,'ONCLICK':'ESettings.SetUrlVar(\'#SHEETNAME#\')'});
			arLines.push({'TEXT':admKDASettingMessages.SEP_SECTION,'TITLE':'#SEP_SECTION# - '+admKDASettingMessages.SEP_SECTION,'ONCLICK':'ESettings.SetUrlVar(\'#SEP_SECTION#\')'});
			arLines.push({'TEXT':admKDASettingMessages.HASH_FILEDS,'TITLE':'#HASH# - '+admKDASettingMessages.HASH_FILEDS,'ONCLICK':'ESettings.SetUrlVar(\'#HASH#\')'});
		}
		
		BX.adminShowMenu(btn, arLines, '');
	},
	
	ShowExtraChooseVal: function(btn)
	{
		var field = $(btn).prev('input')[0];
		this.focusField = field;
		var arLines = [];
		for(var k in admKDASettingMessages.EXTRAFIELDS)
		{
			arLines.push({'TEXT':'<b>'+admKDASettingMessages.EXTRAFIELDS[k].TITLE+'</b>', 'HTML':'<b>'+admKDASettingMessages.EXTRAFIELDS[k].TITLE+'</b>', 'TITLE':'#'+k+'# - '+admKDASettingMessages.EXTRAFIELDS[k].TITLE,'ONCLICK':'javascript:void(0)'});
			for(var k2 in admKDASettingMessages.EXTRAFIELDS[k].FIELDS)
			{
				arLines.push({'TEXT':admKDASettingMessages.EXTRAFIELDS[k].FIELDS[k2], 'TITLE':'#'+k2+'# - '+admKDASettingMessages.EXTRAFIELDS[k].FIELDS[k2],'ONCLICK':'ESettings.SetUrlVar(\'#'+k2+'#\')'});
			}
		}
		BX.adminShowMenu(btn, arLines, '');
	},
	
	ShowPHPExpression: function(link)
	{
		var div = $(link).next('.kda-ie-settings-phpexpression');
		if(div.is(':visible')) div.hide();
		else div.show();
	},
	
	SetUrlVar: function(id)
	{
		if(typeof id=='object') id = id.title;
		var obj_ta = this.focusField;
		//IE
		if (document.selection)
		{
			obj_ta.focus();
			var sel = document.selection.createRange();
			sel.text = id;
			//var range = obj_ta.createTextRange();
			//range.move('character', caretPos);
			//range.select();
		}
		//FF
		else if (obj_ta.selectionStart || obj_ta.selectionStart == '0')
		{
			var startPos = obj_ta.selectionStart;
			var endPos = obj_ta.selectionEnd;
			var caretPos = startPos + id.length;
			obj_ta.value = obj_ta.value.substring(0, startPos) + id + obj_ta.value.substring(endPos, obj_ta.value.length);
			obj_ta.setSelectionRange(caretPos, caretPos);
			obj_ta.focus();
		}
		else
		{
			obj_ta.value += id;
			obj_ta.focus();
		}

		BX.fireEvent(obj_ta, 'change');
		obj_ta.focus();
	},
	
	AddDefaultProp: function(select, type, varname)
	{
		if(!select.value) return;
		var parent = $(select).closest('tr');
		if(!varname)
		{
			var inputName = 'ADDITIONAL_SETTINGS['+(type ? type.toUpperCase() : 'ELEMENT')+'_PROPERTIES_DEFAULT]['+select.value+']';
		}
		else
		{
			var inputName = varname+'['+select.value+']';
		}
		if($(parent).closest('table').find('input[name="'+inputName+'"]').length > 0) return;
		var tmpl = parent.prev('tr.kda-ie-list-settings-defaults');
		var tr = tmpl.clone();
		tr.css('display', '');
		$('.adm-detail-content-cell-l', tr).html(select.options[select.selectedIndex].innerHTML+':');
		$('input[type=text]', tr).attr('name', inputName);
		tr.insertBefore(tmpl);
		$(select).val('').trigger('chosen:updated');
	},
	
	RemoveDefaultProp: function(link)
	{
		$(link).closest('tr').remove();
	},
	
	RemoveLoadingRange: function(link)
	{
		$(link).closest('div').remove();
	},
	
	AddNewLoadingRange: function(link)
	{
		var div = $(link).prev('div');
		var newRange = div.clone().insertBefore(div);
		newRange.show();
	},
	
	ToggleSubparams: function(chb)
	{
		var parent = $(chb).closest('tr');
		var next = parent;
		while((next = next.next('tr.subparams')) && next.length > 0)
		{
			if(chb.checked) next.show();
			else next.hide();
		}
	},
	
	ExportConvCSV: function(link)
	{
		var wm = BX.WindowManager.Get();
		var url = wm.PARAMS.content_url;
		var formId = 'kda-ie-tmpcsvform';
		var form = $(link).closest('form');
		var inputs = $('input[name*="[CONVERSION]"], select[name*="[CONVERSION]"], textarea[name*="[CONVERSION]"], input[name*="[EXTRA_CONVERSION]"], select[name*="[EXTRA_CONVERSION]"], textarea[name*="[EXTRA_CONVERSION]"]', form);
		var newForm = $('<form method="post" target="_blank" id="'+formId+'" style="display: none;"></form>');
		newForm.attr('action', url);
		var tmpInput;
		for(var i=0; i<inputs.length; i++)
		{
			tmpInput = $('<input type="hidden">');
			tmpInput.attr('name', inputs[i].name.replace(/^.*\[(CONVERSION|EXTRA_CONVERSION)\]/, '$1'));
			tmpInput.val($(inputs[i]).val());
			newForm.append(tmpInput);
		}
		newForm.append('<input type="hidden" name="action" value="export_conv_csv">');
		$('#'+formId).remove();
		form.after(newForm);
		newForm.trigger('submit');
		
		return false;
	},
	
	ImportConvCSV: function(link)
	{
		var wm = BX.WindowManager.Get();
		var url = wm.PARAMS.content_url;
		var formId = 'kda-ie-tmpcsvform-import';
		var form = $(link).closest('form');
		var newForm = $('<form method="post" id="'+formId+'" style="display: none;"><input type="file" name="import_file"><input type="hidden" name="action" value="import_conv_csv"></form>');
		newForm.attr('action', url);
		$('#'+formId).remove();
		form.after(newForm);
		$('input[type=file]', newForm).bind('change', function(){
			if(!this.value) return;
			$.ajax({
				url: newForm.attr('action'),
				type: 'POST',
				data: (new FormData(newForm[0])),
				mimeType:"multipart/form-data",
				contentType: false,
				cache: false,
				processData:false,
				success: function(data, textStatus, jqXHR)
				{
					var objData = $(data);
					var w0 = objData.find('#kda-ie-conv-wrap0');
					var w1 = objData.find('#kda-ie-conv-wrap1');
					if(w0.length > 0) $('#kda-ie-conv-wrap0').replaceWith(w0);
					if(w1.length > 0) $('#kda-ie-conv-wrap1').replaceWith(w1);
					ESettings.BindConversionEvents();
					/*if(data && data.substr(0, 1)=='{' && data.substr(data.length-1)=='}')
					{
						eval('var result = '+data+';');
					}
					else
					{
						var result = false;
					}
					
					if(typeof result == 'object')
					{
						if(typeof result.CONV == 'object')
						{
							$('#kda-ie-conv-wrap0 .kda-ie-settings-conversion a.delete').trigger('click');
							for(var i=0; i<result.CONV.length; i++)
							{
								$('#kda-ie-conv-wrap0 a:last').trigger('click');
								for(var j in result.CONV[i])
								{
									$('#kda-ie-conv-wrap0 .kda-ie-settings-conversion:last [name*="['+j+']"]').val(result.CONV[i][j]);
								}
							}
						}
						if(typeof result.EXTRA_CONV == 'object')
						{
							$('#kda-ie-conv-wrap1 .kda-ie-settings-conversion a.delete').trigger('click');
							for(var i=0; i<result.EXTRA_CONV.length; i++)
							{
								$('#kda-ie-conv-wrap1 a:last').trigger('click');
								for(var j in result.EXTRA_CONV[i])
								{
									$('#kda-ie-conv-wrap1 .kda-ie-settings-conversion:last [name*="['+j+']"]').val(result.EXTRA_CONV[i][j]);
								}
							}
						}
					}*/
				}
			});
		}).trigger('click');
	}
}

var EHelper = {
	ShowHelp: function(index)
	{
		var dialog = new BX.CAdminDialog({
			'title':BX.message("KDA_IE_POPUP_HELP_TITLE"),
			'content_url':'/bitrix/admin/'+kdaIEModuleFilePrefix+'_popup_help.php',
			'width':'900',
			'height':'450',
			'resizable':true});
			
		BX.addCustomEvent(dialog, 'onWindowRegister', function(){
			$('#kda-ie-help-faq > li > a').bind('click', function(){
				var div = $(this).next('div');
				if(div.is(':visible')) div.stop().slideUp();
				else div.stop().slideDown();
				return false;
			});
			
			if(index > 0)
			{
				$('#kda-ie-help-tabs .kda-ie-tabs-heads a:eq('+parseInt(index)+')').trigger('click');
			}
		});
			
		dialog.Show();
	},
	
	SetTab: function(link)
	{
		var parent = $(link).closest('.kda-ie-tabs');
		var heads = $('.kda-ie-tabs-heads a', parent);
		var bodies = $('.kda-ie-tabs-bodies > div', parent);
		var index = 0;
		for(var i=0; i<heads.length; i++)
		{
			if(heads[i]==link)
			{
				index = i;
				break;
			}
		}
		heads.removeClass('active');
		$(heads[index]).addClass('active');
		
		bodies.removeClass('active');
		$(bodies[index]).addClass('active');
	}
}

var KdaUninst = {
	Init: function()
	{
		if(!document.getElementById('kda-ie-uninst')) return;
		
		var obj = this;
		$('#kda-ie-uninst input[name="reason"]').bind('change', function(){
			obj.OnReasonChange();
		});
		$('#kda-ie-uninst input[type="submit"]').bind('click', function(){
			obj.OnDeleteModule();
		});
	},
	
	OnReasonChange: function()
	{
		var otherInput = $('#kda-ie-uninst input[name="reason"]:last');
		if(otherInput.length > 0 && otherInput[0].checked) $('#reason_other').show();
		else $('#reason_other').hide();
	},
	
	OnDeleteModule: function()
	{
		if($('#kda-ie-uninst input[name="reason"]:checked').length > 0)
		{
			$('#kda-ie-uninst input[name="step"]').val('2');
		}
	}
}

var KdaOptions = {
	AddRels: function(oLink)
	{
		var table = $(oLink).closest('td').find('table');
		var maxIndex = 0;
		var trs = $('tr[data-index]', table);
		for(var i=0; i<trs.length; i++)
		{
			if(parseInt($(trs[i]).attr('data-index')) > maxIndex) maxIndex = parseInt($(trs[i]).attr('data-index'));
		}
		maxIndex++;
		var tr = $('tr:last', table).clone();
		tr.attr('data-index', maxIndex);
		var newSelect = $('select', $(oLink).closest('div')).clone();
		newSelect.attr('name', $('select:last', tr).attr('name'));
		$('select:last', tr).replaceWith(newSelect);
		var arSelect = $('select', tr);
		for(var i=0; i<arSelect.length; i++)
		{
			$(arSelect[i]).val('');
			arSelect[i].name = arSelect[i].name.replace(/\[[_\d]+\]/, '['+maxIndex+']');
		}
		
		table.append(tr);
	},
	
	ReloadProps: function(oSelect)
	{
		var val = oSelect.value;
		var tr = $(oSelect).closest('tr');
		var newSelect = $(oSelect).closest('table').closest('td').find('.kda-options-rels select').clone();
		newSelect.attr('name', $('select:last', tr).attr('name'));
		if(val.length > 0) $('optgroup[data-id!="'+val+'"]', newSelect).remove();
		$('select:last', tr).replaceWith(newSelect);
	},
	
	RemoveRel: function(oLink)
	{
		if($(oLink).closest('table').find('tr').length > 2)
		{
			$(oLink).closest('tr').remove();
		}
		else
		{
			$(oLink).closest('tr').find('select').val('').trigger('change');
		}
	}
}

$(document).ready(function(){
	if($('#preview_file').length > 0)
	{
		var post = $('#preview_file').closest('form').serialize() + '&ACTION=SHOW_REVIEW_LIST';
		$.post(window.location.href, post, function(data){
			$('#preview_file').html(data);
			if($('.kda-ie-tbl:not([data-init])').length > 0)
			{
				EList.Init();
				$('.kda-ie-tbl').attr('data-init', 1);
			}
		});
	}

	EProfile.Init();
	KdaUninst.Init();
	
	if($('#kda-ie-updates-message').length > 0)
	{
		$.post('/bitrix/admin/'+kdaIEModuleFilePrefix+'.php?lang='+BX.message('LANGUAGE_ID'), 'MODE=AJAX&ACTION=SHOW_MODULE_MESSAGE', function(data){
			data = $(data);
			var inner = $('#kda-ie-updates-message-inner', data);
			if(inner.length > 0 && inner.html().length > 0)
			{
				$('#kda-ie-updates-message-inner').replaceWith(inner);
				$('#kda-ie-updates-message').show();
			}
		});
	}
});