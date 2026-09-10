import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { WebcamImage } from 'ngx-webcam';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {


  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.service.observableDepartment.subscribe(response => {
      this.departments1 = response;
    });
  }

  departments1;

  employees;

  getEmployees(value) {
    this.service.get('employee.php?type=getDeptEmployees&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
  }

 

  
  public webcamImage: WebcamImage = null;
  /** Base64 data URL for visitor photo (from upload or camera), sent as form field "photo" */
  visitorPhoto = '';
  isWebcamOpen = false;

  handleImage(webcamImage: WebcamImage) {
    this.webcamImage = webcamImage;
    this.visitorPhoto = webcamImage && webcamImage.imageAsDataUrl ? webcamImage.imageAsDataUrl : '';
  }

  onWebcamPictureTaken(webcamImage: WebcamImage) {
    this.handleImage(webcamImage);
    this.isWebcamOpen = false;
  }

  onVisitorPhotoSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input && input.files && input.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = () => {
      this.visitorPhoto = reader.result as string;
    };
    reader.readAsDataURL(file);
    input.value = '';
  }

  clearVisitorPhoto(): void {
    this.visitorPhoto = '';
    this.webcamImage = null;
  }


 
  submitted = false;

  saveGatepass(form: { value: object; controls: object; reset: () => void; valid: boolean }) {
    this.submitted = true;
    if (!form.valid) {
      const invalid = [];
      const controls = form.controls;
      for (const name in controls) {
        if (controls[name].invalid && controls[name].errors) {
          invalid.push(name);
        }
      }
      if (typeof alertify !== 'undefined') {
        alertify.error('Please fix the highlighted fields: ' + invalid.join(', '));
      }
      return;
    }
    const temp = { ...form.value, photo: this.visitorPhoto, gatepassType: 'GATEPASS' };
    this.service.post('security/gatepass.php?type=saveGatepassForm', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        if (typeof alertify !== 'undefined') alertify.success('Gate Pass Record Added');
        this.submitted = false;
        this.visitorPhoto = '';
        this.webcamImage = null;
        form.reset();
      } else {
        if (typeof alertify !== 'undefined') alertify.error('An error occurred');
      }
    });
  }






  

  visitorsData;
  phoneNumber = '';
  email = '';
  visitorName = '';
  company = '';
  meetingwith = '';
  purpose = '';
  city = '';
  state = '';
  category = '';
  private checkDataDebounce: any;

  /** Restrict contact field to digits only; on keyup when 10+ digits, fetch existing visitor and auto-fill */
  onContactInput(): void {
    const raw = this.phoneNumber != null ? String(this.phoneNumber) : '';
    const v = raw.replace(/\D/g, '');
    const trimmed = v.length > 12 ? v.slice(0, 12) : v;
    this.phoneNumber = trimmed;
    if (trimmed.length < 10) return;
    if (this.checkDataDebounce) clearTimeout(this.checkDataDebounce);
    this.checkDataDebounce = setTimeout(() => this.CheckData(trimmed), 400);
  }

  CheckData(value: string): void {
    if (!value || value.length < 10) return;
    this.service.get('security/gatepass.php?type=getVisitorData&phoneNumber=' + encodeURIComponent(value)).subscribe((response: any) => {
      this.visitorsData = response;
      if (this.visitorsData && this.visitorsData[0]) {
        const d = this.visitorsData[0];
        this.email = d['email'] || '';
        this.visitorName = d['name'] || d['visitorName'] || '';
        this.company = d['company'] || '';
        this.category = d['category'] || '';
        this.country = (d['other_present_country'] != null && d['other_present_country'] !== '') ? d['other_present_country'] : 'India';
        this.state = d['present_state'] || d['state'] || '';
        this.city = d['present_city'] || d['city'] || '';
      }
    });
  }




  country = 'India';


  countries: string[] = [
    "Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra",
    "Angola", "Anguilla", "Antigua & Barbuda", "Argentina", "Armenia",
    "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas",
    "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium",
    "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia",
    "Bosnia & Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria",
    "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada",
    "Chile", "China", "Colombia", "Costa Rica", "Croatia",
    "Cuba", "Cyprus", "Czech Republic", "Denmark", "Dominican Republic",
    "Ecuador", "Egypt", "El Salvador", "Estonia", "Ethiopia",
    "Fiji", "Finland", "France", "Germany", "Greece",
    "Hong Kong", "Hungary", "Iceland", "India", "Indonesia",
    "Iran", "Iraq", "Ireland", "Israel", "Italy",
    "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya",
    "Kuwait", "Latvia", "Lebanon", "Lithuania", "Luxembourg",
    "Malaysia", "Maldives", "Malta", "Mexico", "Monaco",
    "Mongolia", "Morocco", "Myanmar", "Nepal", "Netherlands",
    "New Zealand", "Nigeria", "Norway", "Oman", "Pakistan",
    "Panama", "Peru", "Philippines", "Poland", "Portugal",
    "Qatar", "Romania", "Russia", "Saudi Arabia", "Singapore",
    "Slovakia", "Slovenia", "South Africa", "South Korea", "Spain",
    "Sri Lanka", "Sweden", "Switzerland", "Syria", "Taiwan",
    "Tanzania", "Thailand", "Trinidad & Tobago", "Tunisia", "Turkey",
    "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America",
    "Uruguay", "Uzbekistan", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe"
  ];



  states: string[] = ["Andhra Pradesh","Andaman and Nicobar Islands","Arunachal Pradesh","Assam",
    "Bihar","Chandigarh","Chhattisgarh","Dadra and Nagar Haveli","Daman and Diu","Delhi",
    "Lakshadweep","Puducherry","Goa","Gujarat","Haryana","Himachal Pradesh","Jammu and Kashmir",
    "Jharkhand","Karnataka","Kerala","Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram",
    "Nagaland","Odisha","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura",
    "Uttar Pradesh","Uttarakhand","West Bengal"
  ];
















}
