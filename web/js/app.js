(function () {

    String.format = function() {
        var theString = arguments[0];
        for (var i = 1; i < arguments.length; i++) {
            var regEx = new RegExp("\\{" + (i - 1) + "\\}", "gm");
            theString = theString.replace(regEx, arguments[i]);
        }
        return theString;
    };

    function loadCurrencies() {
        $.getJSON(
            'app.php/currencies',
            function(currencies) {
                var $currenciesContainer = $('#currenciesContainer');
                $currenciesContainer.empty();
                for (var code in currencies) {
                    $currenciesContainer.append(
                        String.format(
                            '<label class="btn btn-default btn-block currency-label" data-search="{0}"><input class="currency-button" type="radio" name="currenciesRadio" data-code="{1}">{1}:{2}</label>',
                            (code + currencies[code]).toLowerCase(),
                            code,
                            currencies[code]
                        )
                    );
                }
            }
        );
    }

    function loadPairs() {
        $.getJSON(
            'app.php/pairs',
            function(pairs) {
                $('#pairsContainer').empty();
                for (var i = 0; i < pairs.length; i++) {
                    appendPair(pairs[i]);
                }
            }
        );
    }

    function appendPair(pair) {
        var diff = (pair.rate - pair.firstRate).toFixed(2);
        $('#pairsContainer').append(
            String.format(
                '<div class="row"><span id="pairContents{2}">{1}</span><button id="deletePairButton{2}" class="btn btn-default pull-right delete-pair-button" data-pair="{0}" data-id="{2}"><span class="glyphicon glyphicon-remove"></span></button><button id="refreshPairButton{2}" class="btn btn-default pull-right refresh-pair-button" data-pair="{0}" data-id="{2}"><span class="glyphicon glyphicon-refresh"></span></button></div>',
                pair.currencyFrom + '-' + pair.currencyTo,
                formatPair(pair),
                pair.id
            )
        );
        $('#deletePairButton' + pair.id).click(deletePair);
        $('#refreshPairButton' + pair.id).click(refreshPair);
    }

    function formatPair(pair) {
        var diff = (pair.rate - pair.firstRate).toFixed(2);
        return String.format(
            '{0}&nbsp;{1}&nbsp;({2}{3})',
            pair.currencyFrom + '&nbsp;=&gt;&nbsp;' + pair.currencyTo,
            pair.rate.toFixed(4),
            (diff >= 0) ? '+' : '',
            diff
        );
    }

    function savePair() {
        $.getJSON(
            'app.php/pairs/' + $('#currencyFromButton').html() + '-' + $('#currencyToButton').html(),
            function (pair) {
                appendPair(pair);
                showScreen('mainScreen');
            }
        );
    }

    function showScreen(id) {
        $('.navbar').hide();
        $('#' + id + 'Navbar').show();
        $('.screen').hide();
        $('#' + id).show();
    }

    function deletePair() {
        var $this = $(this),
            pairCode = $this.data('pair'),
            id = $this.data('id');
        $.ajax({
            url: 'app.php/pairs/' + pairCode,
            type: 'DELETE',
            success: function(result) {
                $this.parent().remove();
            }
        });
    }

    function refreshPair() {
        var $this = $(this),
            pairCode = $this.data('pair'),
            id = $this.data('id');
        $.getJSON(
            'app.php/pairs/' + pairCode,
            function(pair) {
                $('#pairContents' + id).html(formatPair(pair));
            }
        );
    }

    function updateCurrenciesButtons(button) {
        $('.currency-button')
            .off('change')
            .change(
            function() {
                button.html($(this).data('code'));
                showScreen('addPairScreen');
            }
        );
    }

    function filterCurrencies() {
        var search = $(this).val().toLowerCase();
        $('.currency-label').each(
            function() {
                var $this = $(this);
                $this[( $this.data('search').indexOf(search) > -1 ) ? 'show' : 'hide']();
            }
        );
    }

    function setupAndGoToCurrenciesScreen() {
        updateCurrenciesButtons($(this));
        showScreen('currenciesScreen');
    }

    function goToScreenFactory(screenId) {
        return function () {showScreen(screenId);}
    }

    function initButtons() {
        $('#addPairButton').click(goToScreenFactory('addPairScreen'));
        $('#toMainScreenButton').click(goToScreenFactory('mainScreen'));
        $('#toAddPairScreenButton').click(goToScreenFactory('addPairScreen'));
        $('#currencyFromButton').click(setupAndGoToCurrenciesScreen);
        $('#currencyToButton').click(setupAndGoToCurrenciesScreen);
        $('#savePairButton').click(savePair);
        $('#searchCurrencyInput').keyup(filterCurrencies);
    }

    $(document).ready(function () {
        initButtons();
        loadPairs();
        loadCurrencies();
    });
})();

