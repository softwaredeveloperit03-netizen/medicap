import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DecontaminationComponent } from './decontamination.component';

describe('DecontaminationComponent', () => {
  let component: DecontaminationComponent;
  let fixture: ComponentFixture<DecontaminationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DecontaminationComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DecontaminationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
