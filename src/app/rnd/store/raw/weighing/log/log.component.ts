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

  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getWeighingMaterials();
  }

  getWeighingMaterials() {
    this.service.get('store/raw.php?type=getWeighingMaterials').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  downloadLog(){
    this.service.open('store/raw.php?type=weighingMaterialLogPDF')
  }

  downloadPDF(sign){
    this.service.open('store/raw.php?type=weighingMaterialPDF&pdfsign='+sign+'&id='+this.selectedReport['id']);
  }

}
