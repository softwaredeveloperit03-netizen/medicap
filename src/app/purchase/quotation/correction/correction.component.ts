import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css']
})
export class CorrectionComponent implements OnInit {
  vendors;
 
  isView = false;
  results;
  selectedResult: [];
  vendor_no='';
  vendor_name='';
  status='';
  vendors1= [];
  rights;
  righ;
  currency=[
  { "code": "USD", "name": "US Dollar" },
  { "code": "EUR", "name": "Euro" },
  { "code": "CNY", "name": "Chinese Yuan (Renminbi)" },
  { "code": "JPY", "name": "Japanese Yen" },
  { "code": "GBP", "name": "British Pound Sterling" },
  { "code": "AED", "name": "UAE Dirham" },
  { "code": "CHF", "name": "Swiss Franc" },
  { "code": "CAD", "name": "Canadian Dollar" },
  { "code": "AUD", "name": "Australian Dollar" },
  { "code": "INR", "name": "Indian Rupee" },
  { "code": "HKD", "name": "Hong Kong Dollar" },
  { "code": "SGD", "name": "Singapore Dollar" },
  { "code": "KRW", "name": "South Korean Won" },
  { "code": "NZD", "name": "New Zealand Dollar" },
  { "code": "SEK", "name": "Swedish Krona" },
  { "code": "NOK", "name": "Norwegian Krone" },
  { "code": "ZAR", "name": "South African Rand" }
]
  plant_id:any;

  constructor(private service: DataAccessService) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

   }

   units;
  ngOnInit() {

     this.service.observableUnit.subscribe(response => {
      this.units = response;
    });


    this.getPendingQuotations();
    // this.getPendingQuotationsGeneral();
     this.get_rights();
    //  this.getGst();
    
  }
//----------------------For Pagination---------------------------------//

currentPage: number = 1;
pageSize: number = 10; // Default page size

calculateStartSrNo(): number {
  return (this.currentPage - 1) * 10 ;
}

onPageChange(page: number) {
  this.currentPage = page;
  console.log(this.currentPage);
}

onPageSizeChange(event: any) {
  this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
}
viewf(){
  this.isView=false;
  //  this.getLogs();
  this.currentPage=1;
  this.pageSize =10;
  
}
// ---------------------------------------------------------------------//

gsts;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&form_type=approval&form_name=Quotation Approval&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.righ=this.rights[0].user_access
      console.log(this.righ)
    });
  }
  getPendingQuotations() {
    this.service.get('purchase/quotation.php?type=getPendingQuotationsForCorrection').subscribe(response  => {
      this.results = response;
      
    });
  }
   
 

 view(index: number) {
  this.selectedResult = this.results[index];
  this.isView = true;
  console.log(this.selectedResult);

  for (let mat of this.selectedResult['materials']) {
    if (mat.tax_type === 'Local') {
      mat.currency = 'INR';
      this.getGst(mat, 'Local');
    } else if (mat.tax_type === 'Import') {
      this.getGst(mat, 'Import');
    }
  }
}

getGst(mat: any, value: string) {
  this.service.get('common.php?type=getGST').subscribe((response: any[]) => {
    let filteredGST: any[] = [];

    if (value === 'Local') {
      filteredGST = response.filter(gst => gst.heading === 'GST %');
    } else if (value === 'Import') {
      filteredGST = response.filter(gst => gst.heading === 'Import Duty');
    }

    // Assign filtered GST options to the specific material
    mat.gsts = filteredGST;
  });
}


  updateQuotation(status) {
    let temp={};
    temp['materials'] = this.selectedResult['materials'];
    this.service.post('purchase/quotation.php?type=updateCorrectQuotation&status=' + status +'&id=' + this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPendingQuotations();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  
  }

  filterVendor() {
    this.vendors1 = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['vendor_no'].toUpperCase().includes(this.vendor_no.toUpperCase())) {
        this.vendors1[this.vendors1.length] = material;
      }
    }
  }

  clearFilter(){
    this.vendor_no = '',
    this.vendors1 = this.results;
  }
}
