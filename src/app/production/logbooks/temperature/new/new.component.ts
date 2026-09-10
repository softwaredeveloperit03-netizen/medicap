import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  sections;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getSection();
  }

  getSection(){
    this.service.get('temperature.php?type=getDeptSections').subscribe(response=>{
      this.sections=response;
    });
  }
save(data){
  if(!data.valid){
    alertify.error('All feilds are required!');
    return;
  }
   this.service.post('temperature.php?type=saveTemperature',JSON.stringify(data.value)).subscribe(response=>{
    if(response['status']=='success'){
      alertify.success('data save successfuly');
      this.router.navigate(['/logbooks/temperature']);
      data.resetForm();
    }else{
      alertify.error('some error occured!');
    }
  });

}


}
