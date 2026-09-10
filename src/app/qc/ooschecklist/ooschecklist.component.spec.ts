import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OoschecklistComponent } from './ooschecklist.component';

describe('OoschecklistComponent', () => {
  let component: OoschecklistComponent;
  let fixture: ComponentFixture<OoschecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OoschecklistComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OoschecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
