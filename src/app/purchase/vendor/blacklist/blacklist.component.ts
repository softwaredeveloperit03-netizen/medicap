import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-blacklist',
  templateUrl: './blacklist.component.html',
  styleUrls: ['./blacklist.component.css']
})
export class BlacklistComponent implements OnInit {
  filteredVendor = [];
  results;
  vendor_for = '';
  vendor_name1 = '';
  isView=false;
  selectedResult=[];






  constructor(private service: DataAccessService){ }

  ngOnInit(): void {
    this.getLogs();
  } 


  getLogs() {
    this.service.get('purchase/vendor.php?type=getBlacklistLog&vendor_for=' + this.vendor_for + '&vendor_name=' + this.vendor_name1).subscribe(response => {
      this.results = response;
      this.searchVendor();
      console.log('result',this.results);
    });
  }

  searchVendor() {
    this.filteredVendor = [];
    for (let i = 0; i < this.results.length; i++) {
      let result = this.results[i];
      if (result['vendor_name'].toUpperCase().includes(this.vendor_name1.toUpperCase()) && result['vendor_for'].toUpperCase().includes(this.vendor_for.toUpperCase())) {
        this.filteredVendor[this.filteredVendor.length] = result;
      }
    }
  }
  viewf(){
    this.isView=false;
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  
  }
  download() {
    this.service.open('purchase/vendor.php?type=blacklist_log&material_type=' )
  }

  
  updateunblock(vendor_no,status) {
    this.service.post('purchase/vendor.php?type=updateunblock&status=' + status+ '&vendor_no='+vendor_no, JSON.stringify(status) ).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success(' UnBlock Successfully');
      this.isView = false;
      this.getLogs();
    } else {
      alertify.error('Failed: An error occured, please try again!');
    }
  });
}




}
