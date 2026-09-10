import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SoftwaterplantComponent } from './softwaterplant.component';

describe('SoftwaterplantComponent', () => {
  let component: SoftwaterplantComponent;
  let fixture: ComponentFixture<SoftwaterplantComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SoftwaterplantComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SoftwaterplantComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
