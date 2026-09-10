import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  isView = false;
  results: any[] = [];
  selectedResult: any = {};
  loading = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getCleaningLog();
  }

  getCleaningLog() {
    this.loading = true;
    this.service.getJsonArray('qc/glassware.php?type=getGlasswareCleaningLog').subscribe({
      next: (response: any[]) => {
        this.results = response;
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
      },
    });
  }

  download() {
    this.service.open('qc/glassware.php?type=downloadGlasswareCleaningLog');
  }
}
