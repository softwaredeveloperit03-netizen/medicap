import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sampling-room-log-list',
  templateUrl: './log-list.component.html',
  styleUrls: ['./log-list.component.css']
})
export class LogListComponent implements OnInit {
  logs: any[] = [];
  from_date = '';
  to_date = '';
  loading = false;

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit() {
    const today = new Date();
    const past = new Date();
    past.setMonth(past.getMonth() - 1);
    this.to_date = today.toISOString().substring(0, 10);
    this.from_date = past.toISOString().substring(0, 10);
    this.loadLogs();
  }

  loadLogs() {
    this.loading = true;
    let url = 'qc/sampling/sampling_room.php?type=getSamplingRoomLogs';
    if (this.from_date) url += '&from_date=' + this.from_date;
    if (this.to_date) url += '&to_date=' + this.to_date;
    this.service.get(url).subscribe((res: any) => {
      this.logs = Array.isArray(res) ? res : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  viewLog(log: any) {
    this.router.navigate(['/qc/sampling/sampling-room/view', log.id]);
  }
}
