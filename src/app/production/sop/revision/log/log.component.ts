import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getRevisionRequestLog();
  }

  getRevisionRequestLog() {
    this.service.get('sops.php?type=getRevisionRequests').subscribe(response => {
      this.results = response;
    });
  }

}
