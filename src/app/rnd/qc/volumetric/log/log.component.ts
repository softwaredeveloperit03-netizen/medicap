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

  selectedSolution = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSolutionLog();
  }

  getSolutionLog() {
    this.service.get('qc/volumetric.php?type=getSolutionLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSolution = this.results[index];
    this.isView = true;
  }

}
