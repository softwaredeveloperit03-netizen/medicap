import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InhandComponent } from './inhand.component';

describe('InhandComponent', () => {
  let component: InhandComponent;
  let fixture: ComponentFixture<InhandComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InhandComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InhandComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
