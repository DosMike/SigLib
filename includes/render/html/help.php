<?php

function htmlRender($data) {?><content class="comment">
    <div class="indexbox"><b>Index</b><br><ul>
        <li><a href="#how">How it works</a>
        <li><a href="#search">Search</a>
        <li><a href="#api">API</a>
    </ul></div>
    <h1 id="how">How it works</h1>
    <p>The intended purpose of this plugin is primarily for plugin devs to see, what signatures have already been found, and for server owners to be able to update gamedata files for
    maybe old or unmaintained plugins. It is less so to have a magic or automatic way to get signatures to be always up-to-date. As to how it is used is up to the user base. If you
    have suggestions or feeback, please create an Issue on GitHub, linked in the page footer.</p>
    <p>The data on this website is user provided. In order to upload data or post comments you have to sign in through steam. You can then drag-drop gamedata files on the browser
    to add them to the database. Signed in users can also post comments, please be civil as I don't want to have to moderate this and will rather turn off that feature. Move off-topic
    discussions to DMs.</p>
    <p>All data and comments you post on the database are transparent and visible through your user control panel. If you wish to remove your data from the database, go to your
    user control panel and delete it from there. Deleting your data can not be undone.</p>
    <p>GameData is not linked to your account by a direct ownership relation but instead there is a list of references from values to users. If someone uploads the same signature
    or value this wont create a whole new entry, but create a new reference from the data to the other user. Users can also create and break these references directly using the
    &quot;(De-)Duplicate&quot; button on Signatures and Values.</p>
    <p>If an entry on the database has no remaining duplicates (all users deleted their reference for an entry), the entry will be removed from the database as well.</p>
    <p><b>Duplicates VS Score:</b> Duplicates are supposed to be indicators, that other people found the same value to be working for them. The score or rating is intended as a 
    measure of quality and redundancy. For example if a value is unnecessarily long, users can down vote it and suggest/upload a better value.</p>

    <h1 id="search">Search</h1>
    <p>Filter terms by default search namespace and symbol name. If you want to only search a namespace name, suffix a scope operator (::). If you want to search a label in all 
    namespaces, use the any namespace prefix (*::). Some symbols do not belong to any namespace or the global namespace. These can be search with a plain scope operator (::)
    prefix.<p>
    <p>There are also meta filters in the form key=value to give a more precise result:</p>
    <table>
    <tr><td>user=steamid64</td><td>Allows you to search elements from a specific user</td></tr>
    <tr><td>dupes=range</td><td>Allows you to filter and sort based on the number of duplicate entries a symbol or value got</td></tr>
    <tr><td>score=range</td><td>Allows you to filter and sort based on the user rating a symbol or value got</td></tr>
    <tr><td>version=range</td><td>Allows you to filter and sort based on the game version a symbol was listed for</td></tr>
    <tr><td>date=daterange</td><td>Allows you to filter and sort based on the earliest value that was registered for a symbol</td></tr>
    </table>
    <p>Key and value are separated by one comparator (&lt;, &gt; or =) followed by an integer value. If no comparator was specified the exact version will be searched. Additionally
    you can specify the keyword asc or desc to include this key when sorting results. Both parts are optional but you have to specify at least one. If you use both, use a comma to
    separate both values. If you dont specify a value, the comparator doesn't matter, but the order has to be last.</p>
    <p>For daterange, the value has to be formatted as ISO-date (yyyy-MM-dd). Other formats are not supported.</p>
    <p>The key=value option can not contain any spaces. If they are malformed, they will be interpreted as symbol name.</p>
    <p>Examples for search queries:<ul>
        <li><code>CTFPlayer:: user=76561198023105849 score=desc</code>
        <li><code>score>100</code>
        <li><code>*::Regen version>7695203</code> - This searches symbols for game version 7695204 and newer
        <li><code>date>2022-12-01,asc</code> - Show everything after the december update in ascending order
    </ul></p>

    <h1 id="api">API</h1>
    <p>The API is currently experimental and intended purely for fetching signatures. Simply set the Accept header to one of <code>application/xml</code>, <code>application/json</code>
    or <code>application/vdf</code> to get the response data in the specified format.</p>
    <p>Please keep in mind that this API, as most APIs, requires you to send a User-Agent. You should never copy the User-Agent string of a web browser for the purpse of querying APIs.
    A User-Agent is really just an arbitrary string, identifying your application name and version. You can read more on the MDN here:
        <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent">https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent</a>.</p>
    <p>The following rate limits are currently in place:</p>
    <table>
    <tr><th>Authentication</th><th>per Minute</th><th>per Second</th></tr>
    <tr><td>Unauthorized</td><td>60</td><td>1</td></tr>
    <tr><td>API Key</td><td>120</td><td>-</td></tr>
    </table>
    <p>To generate an API token, go to your user page after logging in. You can use the token from there for the Basic Authorization header like this:<br>
    <code>Authorization: Basic YOUR_TOKEN_HERE</code></p>
    <p>Note on <code>application/vdf</code> queries: You will not magically get a GameData-file out of this (yet). Escape sequences are enabled with vdf rendering; this means that 
    backslashes, quotes and linebreaks are escaped to make the output a bit more robust. Arrays are rendered using duplicate keys, as with vmf files.</p>
    <p>For the endpoints, just watch the network monitor in your browser. Most are not filtered and will just output with your specified renderer.</p>
</content><?
return "Help";
}