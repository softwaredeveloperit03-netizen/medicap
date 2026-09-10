import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;


@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  grades;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getGrades();
  }

getGrades(){
  this.service.get('common.php?type=getGrades').subscribe(response => {
    this.grades= response;
  })
}

save(data){
  if(!data.valid){
    alertify.error('All fields are required');
    return;
  }
  this.service.post('qc/standard/master.php?type=saveMasters', JSON.stringify(data.value)).subscribe(response => {
    if(response ['status']== 'success'){
      alertify.success('Data save succeessfully');
      data.resetForm();
        this.router.navigate(['/qc/standard/master']);
    }else {
      alertify.error('Failed: An error occured, please try again!');
    }
  })
}

}
