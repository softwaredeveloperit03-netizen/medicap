import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeptheadappComponent } from './deptheadapp.component';

describe('DeptheadappComponent', () => {
  let component: DeptheadappComponent;
  let fixture: ComponentFixture<DeptheadappComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeptheadappComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeptheadappComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
