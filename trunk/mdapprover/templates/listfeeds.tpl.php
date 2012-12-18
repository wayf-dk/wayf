        <p>The following feeds are configured:</p>
        <table class="table table-condensed table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>URL</th>
            </tr> 
          </thead>
          <tbody>
            <?php
            foreach ($config_feeds AS $feedkey => $feedvalue) {
                echo "<tr>";
                echo "<td><a href='editfeed.php?feed={$feedkey}'>{$feedkey}</a></td>";
                echo "<td><a href='{$feedvalue['feedurl']}'>{$feedvalue['feedurl']}</a></td>";
                echo "</tr>";
            }
            ?>
          </tbody>
        </table>
