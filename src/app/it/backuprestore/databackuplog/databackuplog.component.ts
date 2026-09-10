import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-databackuplog',
  templateUrl: './databackuplog.component.html',
  styleUrls: ['./databackuplog.component.css']
})
export class DatabackuplogComponent implements OnInit {
  logs: any[] = [];
  loading = false;

  constructor(
    private service: DataAccessService,

  ) { }

  ngOnInit(): void {
    this.getBackupLogs();
  }

  getBackupLogs() {
    this.loading = true;
    this.service
      .get('it/backup.php?type=getBackupLogs')
      .subscribe((response: any) => {
        this.logs = Array.isArray(response) ? response : [];
        this.loading = false;
      }, () => {
        this.loading = false;
        alertify.error('Failed to load backup log');
      });
  }

  manualBackupDownload() {
    const token = encodeURIComponent(localStorage.getItem('token') || '');
    const plant = encodeURIComponent(localStorage.getItem('plant_id') || '');
    const lang = encodeURIComponent(localStorage.getItem('app_language') || 'en');
    const url = this.service.url + 'it/backup.php?type=manualBackupDownload&token=' + token + '&plant_id=' + plant + '&lang=' + lang;
    window.open(url, '_blank');

    alertify.success('Manual backup initiated');
    setTimeout(() => this.getBackupLogs(), 2500);
  }

  refresh() {
    this.getBackupLogs();
  }

  downloadStatus(log: any): string {
    if (log && log.downloaded_from_server === 'Yes') {
      return 'it.backup.manualDownloaded';
    }
    return 'it.backup.autoSaved';
  }
}
