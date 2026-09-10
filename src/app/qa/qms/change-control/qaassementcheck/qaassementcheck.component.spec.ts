import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QaassementcheckComponent } from './qaassementcheck.component';

describe('QaassementcheckComponent', () => {
  let component: QaassementcheckComponent;
  let fixture: ComponentFixture<QaassementcheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QaassementcheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QaassementcheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
