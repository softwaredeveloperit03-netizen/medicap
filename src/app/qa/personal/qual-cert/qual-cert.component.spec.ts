import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QualCertComponent } from './qual-cert.component';

describe('QualCertComponent', () => {
  let component: QualCertComponent;
  let fixture: ComponentFixture<QualCertComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QualCertComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QualCertComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
