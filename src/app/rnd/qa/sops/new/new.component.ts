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

  purpose;
  scope;
  role;
  defination;
  reference;
  process;
  procedures;
  abbreviation;
  training;
  distribution;
  attachments;
  revision;
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

  get_format() {
    this.service.get('sops1.php?type=get_format').subscribe(response => {
      this.equipments = response;
    });
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
    // let temp = data.value;
    const formData = {
      ...data.value,
      purpose: data.value.purpose || false,
      scope: data.value.scope || false,
      role: data.value.role || false,
      defination: data.value.defination || false,
      reference: data.value.reference || false,
      process: data.value.process || false,
      procedures: data.value.procedures || false,
      abbreviation: data.value.abbreviation || false,
      training: data.value.training || false,
      distribution: data.value.distribution || false,
      attachments: data.value.attachments || false,
      revision: data.value.revision || false,
    };
    
    this.service.post('sops.php?type=saveinitiation', JSON.stringify(formData)).subscribe(response => {
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
