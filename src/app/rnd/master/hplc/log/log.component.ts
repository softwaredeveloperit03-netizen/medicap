import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
   
  results;
  isView;

  selectedHplc = [];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getHPLCLog();
  }

  getHPLCLog(){
    this.service.get('qc/hplc.php?type=getHPLCLog').subscribe(response => {
      this.results = response;
    });
  }

  download(){
    this.service.open('qc/hplc.php?type=downloadHPLCLog')
  }

  view(index) {
    this.selectedHplc = this.results[index];
    this.isView = true;
  }

}
