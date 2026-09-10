import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RmpmbookingComponent } from './rmpmbooking.component';

describe('RmpmbookingComponent', () => {
  let component: RmpmbookingComponent;
  let fixture: ComponentFixture<RmpmbookingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RmpmbookingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RmpmbookingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
