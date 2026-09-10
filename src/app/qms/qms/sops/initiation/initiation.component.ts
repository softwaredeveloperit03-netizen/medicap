import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-initiation',
  templateUrl: './initiation.component.html',
  styleUrls: ['./initiation.component.css']
})
export class InitiationComponent implements OnInit {

  init = ({
    height: 300,
    menubar: true,
    content_style: 'body { font-size: 12pt; font-family: Times; }',
    plugins: [
      'advlist autolink lists link image charmap print preview anchor',
      'searchreplace visualblocks code fullscreen',
      'insertdatetime media table paste code help wordcount'
    ],
    toolbar:
      'formatselect | bold italic backcolor | \
      alignleft aligncenter alignright alignjustify | \
      bullist numlist outdent indent | removeformat | help'
  });
  api = 'lh5ymzb4rorw2zhscucerx1013ntad53j7jjnoiokc0pjg8v';

  departments;
  isEquipment = false;
  isOther = false;
  equipments;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  checkFor(value) {
    if (value == 'Equipment') {
      this.isEquipment = true;
      this.isOther = false;
      this.getEquipments();
    } else if (value == 'Other') {
      this.isOther = true;
      this.isEquipment = false;
    } else {
      this.isOther = false;
      this.isEquipment = false;
    }
  }

  getEquipments() {
    this.service.get('equipments.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
    });
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('sops.php?type=saveinitiation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        data.resetForm();
        this.router.navigate(['/qms/sops']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
