#WTYCZKA USOS BONUS

#Instrukcja instalacji:
Najpierw należy zainstalować wtyczkę Wordpress Social Login zmodyfikowaną przez Henryka Michalewskiego:
https://github.com/henrykmichalewski/wordpress-social-login
Potem należy zastąpić plik wp-content\plugins\wordpress-social-login\hybridauth\Hybrid\Providers\Usosweb.php
plikiem wtyczki USOS Bonus.
Następnie należy zainstalować samą wtyczkę - co robimy jak zwykle w wordpressie.
W momencie włączenia wtyczki zostaje zainicjalizowana struktura bazy danych.

#Instrukcja obsługi

Wtyczka dodaje następujące shortcode'y:
db_calendar:
	Wyświetla plan użytkownika na najbliższy tydzień
db_post_homework:
	Pozwala wysłać pracę domową
db_show_homework:
	Pokazuje zadane Tobie prace domowe
db_posted_homework:
	Pokazuje wysłane prace domowe
db_notes_declaration:
	Pozwala zobaczyć i tworzyć deklaracje na notatki
db_show_points:
	Pokazuje Twoje punkty ściągnięte z USOSa

Dodatkowo wtyczka dodaje nowe opcje w ustawieniach w kokpicie:
Dodaj wydarzenie:
	Pozwala dodać nowe wydarzenie do kalendarza.
Ustawienia prac domowych:
	Pozwala dodać siebie do grupy prac domowych.
Wyślij maila do grupy (wymaga podwyższonych uprawnień):
	Pozwala wysłać maila do grupy prac domowych.
Przyznaj odznakę (wymaga podwyższonych uprawnień):
	Pozwala przyznać odznakę za aktywność użytkownikowi.

Liczba zdobytych odznak wyświetla się na górze na pasku.

#Opis działania:

Deklaracje na notatki działają tak, że jak stworzy się deklarację, to w tabeli jest widoczne ID deklaracji.
Potem, kiedy piszemy posta i chcemy oznaczyć go jako te notatki, to musimy dodać własne pole do posta.
Nazwa pola to "deklaracja_id", wartość to ID odpowiadające deklaracji w tabeli.

Prace domowe można zadawać tylko grupom prac domowych. Każdy użytkownik może dodać się do jakiejś grupy
prac domowych (w kokpicie), i potem w tabeli wyświetlanej przez db_show_homework możemy zobaczyć wszystkie prace domowe
zadane grupom w których jesteśmy. Żeby zadać pracę domową, musimy napisać posta i dodać mu własne pola.
Te pola to:
	homework_deadline - termin oddania pracy domowej w formacie dzień.miesiąc.rok
	homework_group - grupa prac domowych, której zadajemy pracę
	homework_number - jakiś unikalny numer pracy domowej
Jeśli któregokolwiek z tych pól zabraknie, post nie zostanie zakwalifikowany jako praca domowa.
Żeby oddać pracę domową musimy użyć formularza db_post_homework. Żeby zobaczyć wszystkie wysłane prace domowe
używamy shortcode'u db_posted_homework.

Kalendarz wyświetla wszystkie zajęcia wpisane na usosie i nasze własne dodane z kokpitu wydarzenia.
Zwykłe wydarzenia są wyświetlane normalnie, alerty są pogrubione. Dodatkowo, wtyczka wspiera wyświetlanie alertów
w stopce, ale należy je samodzielnie dodać. W tym celu idziemy do Kokpit->Wygląd->Widgety, przeciągamy
widget "Tekst" do głównej strefy widgetów (czy gdzie tam chcemy) i w treści tekstu wpisujemy następujący napis:
<?php display_alerts(); ?>
To spowoduje wyświetlanie alertów w stopce (albo tam, gdzie jest ten widget tekstowy).

UWAGA! Twórca tego projektu ma wtyczkę żeńską i lubi wkładać męskie.