import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EtpSheetComponent } from './etp-sheet.component';

describe('EtpSheetComponent', () => {
  let component: EtpSheetComponent;
  let fixture: ComponentFixture<EtpSheetComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EtpSheetComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EtpSheetComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
