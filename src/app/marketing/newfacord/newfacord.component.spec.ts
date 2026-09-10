import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewfacordComponent } from './newfacord.component';

describe('NewfacordComponent', () => {
  let component: NewfacordComponent;
  let fixture: ComponentFixture<NewfacordComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewfacordComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewfacordComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
