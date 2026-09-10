import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-mrp-approval',
  templateUrl: './mrp-approval.component.html',
  styleUrls: ['./mrp-approval.component.css']
})
export class MrpApprovalComponent implements OnInit {
  results;
  product_code='';
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.mrpApp();
  }
  mrpApp(){
    this.service.get('qa/product.php?type=getPendingMRPs').subscribe(response=>{
      this.results=response;
    })
  }

  // approveMRP(status,id){
  //   let temp;
  //   temp['product_code']=this.results['product_code'];
  //   temp['id']=id;
  //   temp['status']=status;
  //   this.service.post('qa/product.php?type=approveMRP',JSON.stringify(temp)).subscribe(response=>{
  //    if(response['status']=='success'){
  //      alert('Product Mrp will be Updated Succesuly');
  //    }else{
  //     alert('not save');
  //    }
  //   });
  // }

  approveMRP(status,result) {
    result['status'] = status;
    this.service.post('qa/product.php?type=approveMRP' ,JSON.stringify(result)).subscribe(response => {
      if (response['status']) {
        alert('Product Mrp Updated Successfully');
        this.mrpApp();
        this.router.navigate(['/product'])
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
