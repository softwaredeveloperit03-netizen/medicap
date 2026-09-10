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
  material_subtype='';
  vendor_no='';
  status='';
  material_type='';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getGRNLog();
    this.getVendors();
  }

  getGRNLog() {
    this.service.get('store/packing.php?type=getGRNLog&material_type='+this.material_type +'&vendor_no='+this.vendor_no +'&status='+status).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
  download() {
    let url = this.service.url + "pdf1/grn-preparation.php?id=" + this.selectedReport['id'] + '&token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

}
