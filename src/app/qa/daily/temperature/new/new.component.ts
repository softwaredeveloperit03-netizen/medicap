import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  depart;
  sections;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getDepartment();
  }
  getDepartment() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.depart = response;
    });
  }
  getSections(index) {
    index = index - 1;
    if (index !== -1) {
      let temp = this.depart[index];
      this.sections = temp['sections'];
    }
  }

  save(Form) {
   
    this.service
      .post(
        'qa/temperature.php?type=saveDailyTemperature',
        JSON.stringify(Form.value)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          // this.router.navigate(['/hr/asset']);
          alertify.success('data save Successfuly');
          Form.resetForm();
        } else {
          alertify.error('Error Occured');
        }
      });
  }
  // save(data){
  //   if(data.valid)
  //   this.service.post('qa/temperature.php?type=saveDailyTemperature',JSON.stringify(data.value)).subscribe(response=>{
  //     alert("saved succesfully");
  //     data.reset();
  //   });
  //   else{
  //     alert("not valid")
  //   }
  // }
}
