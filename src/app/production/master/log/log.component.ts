import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isNew = false;
  results;
  

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getBatchFormulaLog();
  }

  getBatchFormulaLog() {
    this.service.get('production/master.php?type=getBatchFormulaLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }

  download(type){
    if(type == 'manual'){
      this.service.open('pdf1/production.php?type=bmr&id='+this.selectedResult['id']);
    }else{
      this.service.open('pdf1/production.php?type=bmrdigital&id='+this.selectedResult['id']);
    }
  }

}
