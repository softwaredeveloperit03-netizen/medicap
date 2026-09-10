import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
declare let alertify;
@Component({
  selector: 'app-qualification-master',
  templateUrl: './qualification-master.component.html',
  styleUrls: ['./qualification-master.component.css']
})
export class QualificationMasterComponent implements OnInit {
  qualifications;
  isNewQualification = false;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getQualifications();
  }

  getQualifications() {
    this.service.get('hrDepartment.php?type=getQualifications')
    .subscribe(response => {
      this.qualifications = response;
    });
  }

  addQualification(qualificationForm) {
    this.isNewQualification = false;
    this.service.post('hrDepartment.php?type=addQualification', JSON.stringify(qualificationForm.value))
    .subscribe(response => {
      if(response['status']=='success'){
        qualificationForm.reset();
        this.getQualifications();
        this.isNewQualification = false;
        alertify.success("save successfully")
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }
}
