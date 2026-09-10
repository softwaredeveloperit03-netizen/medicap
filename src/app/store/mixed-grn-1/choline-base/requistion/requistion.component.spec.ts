import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RequistionComponent } from './requistion.component';

describe('RequistionComponent', () => {
  let component: RequistionComponent;
  let fixture: ComponentFixture<RequistionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RequistionComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RequistionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
