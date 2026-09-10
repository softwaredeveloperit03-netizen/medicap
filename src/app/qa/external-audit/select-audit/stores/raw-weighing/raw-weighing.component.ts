import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-raw-weighing',
  templateUrl: './raw-weighing.component.html',
  styleUrls: ['./raw-weighing.component.css'],
  providers: [DatePipe]
})
export class RawWeighingComponent implements OnInit {

  isView = false;
  results;
  challan_for='';
  material_type='';
  from_date = '';
  to_date = '';
  today='';
  selectedReport = [];
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit() {
    this.getWeighingMaterials();
  }

  getWeighingMaterials() {
    this.service.get('store/raw.php?type=getWeighingMaterials&material_type='+this.material_type+'&from_date='+this.from_date+'&to_date='+this.to_date+'&challan_for='+this.challan_for).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  downloadLog(){
    this.service.open('store/raw.php?type=weighingMaterialLogPDF&material_type='+this.material_type+'&from_date='+this.from_date+'&to_date='+this.to_date+'&challan_for='+this.challan_for)
  }

  downloadPDF(sign){
    this.service.open('store/raw.php?type=weighingMaterialPDF&pdfsign='+sign+'&id='+this.selectedReport['id']);
  }

}
