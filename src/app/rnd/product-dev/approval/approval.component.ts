import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  selectedResult; 
  isView;

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingProducts()
  }
  getPendingProducts(){
    this.service.get('rnd/product.php?type=getPendingProducts').subscribe(response=>{
      this.results=response;
    });
  }
  update(status,id){
    this.service.get('rnd/product.php?type=updateProduct&status='+status+ '&id='+id).subscribe(response=>{
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.isView = false;
        this.getPendingProducts();
      }else{
        alertify.error('An Error Occured, Please try again!');
      }
    });
  }
  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

}
