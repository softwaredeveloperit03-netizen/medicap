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
  vendor_no='';
  status='';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDedustingsLog();
    this.getVendors();
  }

  getDedustingsLog() {
    this.service.get('store/packing.php?type=getDedustingsLog&material_type='+this.material_type +'&vendor_no='+this.vendor_no +'&status='+status).subscribe(response => {
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
