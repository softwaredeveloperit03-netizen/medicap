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
  vendors;
  selectedReport = [];
  material_type='';
  status='';
  vendor_no='';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDedustingMaterials();
    this.getVendors();
  }

  getDedustingMaterials() {
    this.service.get('store/raw.php?type=getDedustingMaterials&vendor_no='+ this.vendor_no+'&material_type='+this.material_type+'&status='+this.status).subscribe(response => {
      this.results = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  downloadPDF(sign){
    this.service.open('store/raw.php?type=dedustingMaterialPDF&pdfsign='+sign+'&id=' + this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('store/raw.php?type=dedustingMaterialLogPDF&vendor_no='+ this.vendor_no+'&material_type='+this.material_type+'&status='+this.status)
  }

}
