import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RequesitionComponent } from './requesition.component';

describe('RequesitionComponent', () => {
  let component: RequesitionComponent;
  let fixture: ComponentFixture<RequesitionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RequesitionComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RequesitionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
