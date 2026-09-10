import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  results;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getGRNLog();
  }

  getGRNLog() {
    this.service.get('store/label.php?type=getAwaitingGRNRawLabels').subscribe(response => {
      this.results = response;
    });
  }

  printLabel(index){
    let selectedResult = this.results[index];
    this.service.open('store/raw.php?type=grnLabelsPDF&id='+selectedResult['id'] + '&material_name='+ selectedResult['material_name']+'&total_containers='+selectedResult['total_containers']+'&batch_no='+selectedResult['batch_no']+'&grade='+selectedResult['grade']);
  }

}
