import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BfrlogComponent } from './bfrlog.component';

describe('BfrlogComponent', () => {
  let component: BfrlogComponent;
  let fixture: ComponentFixture<BfrlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BfrlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BfrlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
