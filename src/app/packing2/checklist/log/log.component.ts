import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isview=false;
  results;
  selectedResult;
  stages;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getStages();
  }

  getStages(){
    this.service.get("production/stage.php?type=get_iqpc_stages&material_type='Packing Material'").subscribe(response => {
      this.results = response;
    })
  }

  view(val){
    this.selectedResult=this.results[val];
    this.stages = JSON.parse(this.selectedResult['stages_test']);
    this.isview = true;
  }

}
