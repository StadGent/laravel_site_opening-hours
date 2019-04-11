// row status
export const SERVICE_NO_CH = 'Geen kanalen';
export const SERVICE_MISSING_OH = 'Ontbrekende kalender(s)';
export const SERVICE_INACTIVE_OH = 'Ontbrekende actieve kalender(s)';
export const SERVICE_COMPLETE = '✓ Volledig';
export const SERVICE_NO_CH_TOOLTIP = 'Deze dienst heeft geen kanalen.';
export const SERVICE_MISSING_OH_TOOLTIP = 'Minstens 1 van de kanalen van deze dienst heeft geen versie.';
export const SERVICE_INACTIVE_OH_TOOLTIP = 'Alle kanalen hebben een versie maar minstens 1 kanaal heeft geen versie die nu geldt. Een versie geldt niet als deze verlopen is of pas in de toekomst actief wordt.';
export const SERVICE_COMPLETE_TOOLTIP = 'Alle kanalen hebben minstens een kalenderversie die nu geldig is.';

// roles
export const MEMBER = 'Lid';
export const OWNER = 'Eigenaar';
export const ADMIN = 'Admin';

// API
export const API_PREFIX = '/api/v1/ui';

// Validation errors
export const NO_VALID_EMAIL = 'Dit is geen geldig e-mail adres';
export const CHOOSE_SERVICE = 'Kies een dienst';
export const OH_INVALID_RANGE = 'Er mogen geen uitzonderingen beginnen voor de start of eindigen na het einde, van de de nieuwe begin/einddatum van de openingsurenversie.' +
    '<br>De wijziging werd niet doorgevoerd, controleer of er uitzonderingen vroeger of later vallen dan de nieuwe gekozen tijdsperiode.';
export const UNKNOWN_ERROR = 'Er is een onbekende fout opgetreden.';
export const VAGUE_ERROR = 'Neem een print screen en neem contact op met de servicedesk.';
export const ID_MISSING = 'ID ontbreekt';
export const COULD_NOT_DENY_ACCESS = 'Toegang kon niet ontzegd worden.';
export const EVENT_INVALID_RANGE = 'Een event mag niet starten of eindigen buiten de periode van de kalender.';
export const IS_RECREATEX = 'Recreatex data kan hier niet gewijzigd worden.';
export const START_AFTER_END = 'De start ligt na het einde.';
export const START_AFTER_UNTIL = 'De begindatum ligt na de einddatum.';
export const NAME_CANNOT_BE_EXCEPTION = 'De naam van de uitzondering kan niet "uitzondering" zijn.';
export const NAME_REQUIRED = 'De uitzondering moet een naam hebben.';
export const NO_EVENTS = 'Er zijn geen gebeurtenissen in deze uitzondering.';
