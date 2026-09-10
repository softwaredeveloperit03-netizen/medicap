import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RightlogComponent } from './rightlog.component';

describe('RightlogComponent', () => {
  let component: RightlogComponent;
  let fixture: ComponentFixture<RightlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RightlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RightlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
