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
    this.service.get('store/packing.php?type=getPendingGRNLabels').subscribe(response => {
      this.results = response;
    });
  }

  printLabel(index){
    let selectedResult = this.results[index];
    this.service.open('store/packing.php?type=grnLabelsPDF&id='+selectedResult['id'] + '&material_name='+ selectedResult['material_name']+'&label_count='+selectedResult['label_count']+'&batch_no='+selectedResult['batch_no']+'&grade='+selectedResult['grade']);
  }

}
