import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PrevencheckComponent } from './prevencheck.component';

describe('PrevencheckComponent', () => {
  let component: PrevencheckComponent;
  let fixture: ComponentFixture<PrevencheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PrevencheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PrevencheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
