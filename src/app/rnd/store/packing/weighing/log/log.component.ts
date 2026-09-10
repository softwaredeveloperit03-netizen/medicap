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
  vendor_no='';
  status='';
  material_type='';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getWeighingMaterials();
    this.getVendors();
  }

  getWeighingMaterials() {
    this.service.get('store/packing.php?type=getWeighingMaterials&material_type='+this.material_type+'&status='+this.status+'&vendor_no='+this.vendor_no).subscribe(response => {
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

}
