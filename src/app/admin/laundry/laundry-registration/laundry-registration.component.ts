import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-laundry-registration',
  templateUrl: './laundry-registration.component.html',
  styleUrls: ['./laundry-registration.component.css']
})
export class LaundryRegistrationComponent implements OnInit {

    states: any;

    constructor(private service: DataAccessService,private router: Router) { }
  ngOnInit(): void {
    this.getState();
  }

  getState(){
    this.service.get('common.php?type=getStates').subscribe(response=>{
      this.states=response;
    })
  }
  
  save(Form){
    if(!Form.valid){
      alertify.error('All fields are required');
      return;
    }
    this.service.post('hr/asset.php?type=saveAsset',JSON.stringify(Form.value)).subscribe(response=>{
      if(response['status']=='success'){
        this.router.navigate(['/hr/asset']);
        alertify.success('data save Successfuly');
        Form.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }


}
