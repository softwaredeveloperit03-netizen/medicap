import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  imports: [FormsModule],
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  departments;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
  saveForm(Form){
    if(!Form.valid){
      alertify.error('All fields are required');
      return;
    }
    this.service.post('qa/document.php?type=saveAsset',JSON.stringify(Form.value)).subscribe(response=>{
      if(response['status']==='success'){
        // this.router.navigate(['/hr/asset']);
        alertify.success('data save Successfuly');
        Form.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }

}