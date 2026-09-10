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
  departments: any[] = [];
 
  constructor(public service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response: any) => {
      this.departments = Array.isArray(response) ? response : [];
    });
  }

  savemaster(data: any){
    if (!data || !data.valid) {
      alertify.error('Please fill required fields');
      return;
    }

    this.service.post('hr/asset.php?type=mastersave',JSON.stringify(data.value)).subscribe((response: any)=>{
      if(response['status']==='success'){
        this.router.navigate(['/master/hra/asset/master']);
        alertify.success('data save Successfuly');
        data.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }
}
