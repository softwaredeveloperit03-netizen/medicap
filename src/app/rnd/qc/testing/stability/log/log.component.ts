import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  isView = false;
  results;

  selectedTesting = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingStabilityTestings();
  }

  getPendingStabilityTestings() {
    this.service.get('stability.php?type=getStabilityTestingLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedTesting = this.results[index];
    this.isView = true;
  }

}
