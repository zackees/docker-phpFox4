<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if !empty($sPhoneFieldId)}
    <script>
        var IntlTel_{$sUniqueKey} = null, IntlTelCheck_{$sUniqueKey} = false;
        $Behavior.buildPhoneCountryCode{$sUniqueKey} = function() {l}
            if (typeof intlTelInput !== "function" || IntlTel_{$sUniqueKey} !== null) {l} return false; {r}
            if($("{$sPhoneFieldId}").length) {l}
                {if !empty($bInitOnChange)}
                        $("{$sPhoneFieldId}").on('change', function() {l}
                            var ele = $(this), value = ele.val(),
                                matched = value.match({literal}/^(\+\d{1,2}\s?)?1?\-?\.?\s?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/g{/literal});
                            if (matched !== null) {l}
                                IntlTel_{$sUniqueKey} = intlTelInput(document.querySelector("{$sPhoneFieldId}"), {l}
                                    allowDropdown: true,
                                    autoPlaceholder: "polite",
                                    autoHideDialCode: false,
                                    nationalMode: true,
                                    initialCountry: "auto",
                                    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@8.0.0/build/js/utils.js",
                                    formatOnDisplay: false,
                                    geoIpLookup: function(success) {l}
                                            try {l}
                                                $.get("https://ipinfo.io", function() {l}{r}, "jsonp").always(function(resp) {l}
                                                    var countryCode = (resp && resp.country) ? resp.country : "us";
                                                    success(countryCode);
                                                {r});
                                            {r} catch(e) {l}{r}
                                        {r},
                                    {r});
                                document.querySelector("{$sPhoneFieldId}").addEventListener('countrychange', function(){l}
                                    $("{$sPhoneFieldId}").trigger('change');
                                {r});
                                setTimeout(function() {l}
                                    if (IntlTel_{$sUniqueKey} && IntlTel_{$sUniqueKey}.getNumber()) {l}
                                        $("{$sPhoneFieldId}").val(IntlTel_{$sUniqueKey}.getNumber(1));
                                    {r}
                                {r}, 600);
                                $("{$sPhoneFieldId}").closest('.form-group').addClass('iti-init-phone');
                            {r} else {l}
                                $Core.ajax('user.validatePhoneNumber', {l}
                                    type: 'GET',
                                    params: {l}
                                        'phone': $(this).val()
                                    {r},
                                    success: function (sOutput) {l}
                                        var data = JSON.parse(sOutput);
                                        if (!data.is_valid) {l}
                                            if(IntlTel_{$sUniqueKey}) {l}
                                                IntlTel_{$sUniqueKey}.destroy();
                                                $("{$sPhoneFieldId}").closest('.form-group').removeClass('iti-init-phone');
                                                IntlTel_{$sUniqueKey} = null;
                                            {r}
                                        {r} else {l}
                                            IntlTel_{$sUniqueKey} = intlTelInput(document.querySelector("{$sPhoneFieldId}"), {l}
                                                allowDropdown: true,
                                                autoPlaceholder: true,
                                                autoHideDialCode: false,
                                                nationalMode: true,
                                                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@8.0.0/build/js/utils.js",
                                                formatOnDisplay: false,
                                            {r});
                                            if (data.country_code) {l}
                                                IntlTel_{$sUniqueKey}.setCountry(data.country_code);
                                            {r}
                                            document.querySelector("{$sPhoneFieldId}").addEventListener('countrychange', function(){l}
                                                $("{$sPhoneFieldId}").trigger('change');
                                            {r});
                                            setTimeout(function() {l}
                                                if (IntlTel_{$sUniqueKey} && IntlTel_{$sUniqueKey}.getNumber()) {l}
                                                    $("{$sPhoneFieldId}").val(IntlTel_{$sUniqueKey}.getNumber(1));
                                                {r}
                                            {r}, 600);
                                            $("{$sPhoneFieldId}").closest('.form-group').addClass('iti-init-phone');
                                        {r}
                                    {r}
                                {r});
                            {r}
                        {r})
                        if (!IntlTelCheck_{$sUniqueKey} && $("{$sPhoneFieldId}").val()) {l}
                            $("{$sPhoneFieldId}").trigger('change');
                            IntlTelCheck_{$sUniqueKey} = true;
                        {r}
                {else}
                    IntlTel_{$sUniqueKey} = intlTelInput(document.querySelector("{$sPhoneFieldId}"), {l}
                        allowDropdown: true,
                        autoPlaceholder: "polite",
                        autoHideDialCode: false,
                        initialCountry: "auto",
                        nationalMode: true,
                        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@8.0.0/build/js/utils.js",
                        formatOnDisplay: false,
                        geoIpLookup: function(success) {l}
                            try {l}
                            $.get("https://ipinfo.io", function() {l}{r}, "jsonp").always(function(resp) {l}
                                var countryCode = (resp && resp.country) ? resp.country : "us";
                                success(countryCode);
                            {r}); {r} catch(e) {l}{r}
                        {r}
                    {r});
                    {if !empty($sDefaultNumber)}
                        IntlTel_{$sUniqueKey} ? IntlTel_{$sUniqueKey}.setNumber("{$sDefaultNumber}") : null;
                    {/if}
                    $("{$sPhoneFieldId}").on('change', function() {l}
                        setTimeout(function() {l}
                            if (IntlTel_{$sUniqueKey} && IntlTel_{$sUniqueKey}.getNumber()) {l}
                                $("{$sPhoneFieldId}").val(IntlTel_{$sUniqueKey}.getNumber(1));
                            {r}
                        {r}, 500);
                    {r});
                    document.querySelector("{$sPhoneFieldId}").addEventListener('countrychange', function() {l}
                        $("{$sPhoneFieldId}").trigger('change');
                    {r})
                {/if}
                $("{$sPhoneFieldId}").closest('form').on('submit', function() {l}
                    if (IntlTel_{$sUniqueKey}) {l}
                        $("{$sPhoneFieldId}").val(IntlTel_{$sUniqueKey}.getNumber(1));
                    {r}
                {r})
            {r}
        {r}
    </script>
{/if}