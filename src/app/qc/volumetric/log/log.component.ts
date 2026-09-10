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

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVolumetricPreparationLog();
  }

  selectedResult: any = {};

  getVolumetricPreparationLog() {
    this.service.get('qc/volumetric.php?type=getVolumetricPreparationLog').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  view(index: number) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  viewPhoto() {
    window.open(this.service.url + '../../upload/volumetric/' + this.selectedResult['weigh_slip']);
  }

  download() {
    this.service.open('qc/volumetric.php?type=downloadSolutionLog');
  }
}
