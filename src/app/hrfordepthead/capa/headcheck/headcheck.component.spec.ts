import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HeadcheckComponent } from './headcheck.component';

describe('HeadcheckComponent', () => {
  let component: HeadcheckComponent;
  let fixture: ComponentFixture<HeadcheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ HeadcheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HeadcheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
