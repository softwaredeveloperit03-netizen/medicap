import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new_correction',
  templateUrl: './new_correction.component.html',
  styleUrls: ['./new_correction.component.css']
})
export class New_correctionComponent implements OnInit {
  results;
  selectedReport = [];
  isView = false;
  po_no='';
  data;
  vendors;
  damage;
  lists;
  result = [];
  router: any;
 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getRejectedChallans();
    this.getVendors();
  }
  getVendors(){
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
  getRejectedChallans() {
    this.service.get('store/challan.php?type=getRejectedChallans').subscribe(response => {
      this.results = response;
    })
  }
  viewResult(index) {
    this.selectedReport = this.results[index];
    this.damage = this.selectedReport['receiving_details'];
    console.log(this.damage)
    this.isView = true;
  }
  // addData(data) {
  //   if (!data.valid) {
  //     alert('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   let tempData = [];
  //   this.result[this.result.length] = temp;
  //   console.log(this.result);
  //   data.resetForm();
  // }
  save(data) {
   
     
      let temp = data.value;
      temp['result']=this.result;
      this.service.post('store/challan.php?type=edits',JSON.stringify(data.value)).subscribe(response => 
      {
        if (response['status'] == 'success') {
          alert('data Saved Successfully');
          console.log(this.result);
          this.result = [];
          // this.router.navigate(['/master/weight/new']);
        } else {
          console.log(response);
          alert('Failed: An error occured, please try again!');
        }
      });
    
  }

}
