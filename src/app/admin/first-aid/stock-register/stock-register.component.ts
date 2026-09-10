import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-stock-register',
  templateUrl: './stock-register.component.html',
  styleUrls: ['./stock-register.component.css']
})
export class StockRegisterComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDetails();
  }

data;
getDetails()
{
  this.service.get('qa/all2.php?type=getfirst_aid_material').subscribe((response:any) => {
    this.data = response;
   
  });
}

date;department;requisition_by;contact_info;item_desc;uom
isView=false;
selectedResult=[];
view(index){
  this.selectedResult=this.data[index]
  this.isView=true;
  this.date=this.selectedResult['date'];
  this.department=this.selectedResult['department'];
  this.requisition_by=this.selectedResult['requisition_by'];
  this.contact_info=this.selectedResult['contact_info'];
  this.item_desc=this.selectedResult['item_desc'];
  this.uom=this.selectedResult['uom'];
  
}


save(data) {
  console.log(data.value);
  if (!data.valid) {
    alert('All fields are required');
    return;
  }

  this.service.post('qa/all2.php?type=save_registration', JSON.stringify(data.value)).subscribe(response => {
    if (response['status'] == 'success') {
      alert('Saved Successfully');
      // this.router.navigate(['/checklist']);
    } else {
      console.log(response);
      alert('Failed: An error occured, please try again!');
    }
  });
}


}
