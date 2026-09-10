import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UploadReportComponent } from './upload-report.component';

describe('UploadReportComponent', () => {
  let component: UploadReportComponent;
  let fixture: ComponentFixture<UploadReportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UploadReportComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UploadReportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
